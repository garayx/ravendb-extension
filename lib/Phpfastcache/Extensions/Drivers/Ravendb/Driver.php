<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Extensions\Drivers\Ravendb;

use Composer\InstalledVersions;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheDriverConnectException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedMethodException;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Operations\CollectionStatistics;
use RavenDB\Documents\Operations\DeleteByQueryOperation;
use RavenDB\Documents\Operations\DetailedDatabaseStatistics;
use RavenDB\Documents\Operations\GetCollectionStatisticsOperation;
use RavenDB\Documents\Operations\GetDetailedStatisticsOperation;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Exceptions\RavenException;
use RavenDB\Http\ServerNode;
use RavenDB\ServerWide\Operations\BuildNumber;
use RavenDB\ServerWide\Operations\Configuration\GetDatabaseSettingsOperation;
use RavenDB\ServerWide\Operations\GetBuildNumberOperation;
use RavenDB\Type\Duration;

/**
 * Class Driver
 * @property DocumentSession $instance Instance of driver service
 * @method Config getConfig()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    public const RAVENDB_DEFAULT_DB_NAME = 'phpfastcache';
    public const RAVENDB_DEFAULT_COLLECTION_NAME = 'phpfastcache';
    public const RAVENDB_DOCUMENT_PREFIX = 'pfc/';

    protected DocumentStore $documentStorage;
    protected string $documentPrefix;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('ds');
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
To install the Raven client library via Composer:
<code>composer require "ravendb/ravendb-php-client:^5.2"</code>
The "ravendb/ravendb-php-client" library also require the php "ds" (datastructure) extension.
HELP;
    }

    /**
     * @return bool
     * @throws PhpfastcacheDriverConnectException
     */
    protected function driverConnect(): bool
    {
        try {
            $authOptions = $this->getConfig()->getAuthOptions();
            $this->documentPrefix = $this->getConfig()->getDocumentPrefix();
            $this->documentStorage = new DocumentStore($this->getConfig()->getHost(), $this->getDatabaseName());
            if ($authOptions) {
                $this->documentStorage->setAuthOptions($authOptions);
            }
            $this->documentStorage->getConventions()->setFindCollectionName(fn () => $this->getCollectionName());
            $this->documentStorage->getConventions()->setFindIdentityProperty(static fn () => 'key');
            $this->documentStorage->getConventions()->setRequestTimeout(Duration::ofSeconds(1));
            $this->documentStorage->initialize();
            $this->documentStorage->getRequestExecutor()->setDefaultTimeout(Duration::ofSeconds(1));
            $this->instance = $this->documentStorage->openSession();// @phpstan-ignore-line
            if ($this->documentStorage->maintenance()->send(new GetDatabaseSettingsOperation($this->getDatabaseName())) === null) {
                throw new RavenException('Unable to fetch databases metadata.');
            }
        } catch (RavenException $e) {
            throw new PhpfastcacheDriverConnectException('Unable to connect to Raven server: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     * @throws PhpfastcacheDriverException
     * @throws \Exception
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        return $this->getRavenDocument($item)?->toDriverArray();
    }

    protected function getRavenDocument(ExtendedCacheItemInterface $item): ?RavenProxy
    {
        $ravenDocument = $this->instance->load(
            RavenProxy::class,
            $this->documentPrefix . $item->getKey()
        );

        if ($ravenDocument instanceof RavenProxy) {
            $ravenDocument->setDetailedDate($this->getConfig()->isItemDetailedDate());
            return $ravenDocument;
        }

        return null;
    }

    /**
     * @param ExtendedCacheItemInterface ...$items
     * @return array<array<string, mixed>>
     * @throws PhpfastcacheDriverException
     */
    protected function driverReadMultiple(ExtendedCacheItemInterface ...$items): array
    {
        $ravenDocuments = $this->instance->load(
            RavenProxy::class,
            $this->getKeys($items, false, $this->documentPrefix)
        );

        if (is_iterable($ravenDocuments)) {
            $ravenDocuments = iterator_to_array($ravenDocuments);

            return array_combine(
                array_map(fn(?RavenProxy $ravenProxy) => $ravenProxy?->getKey(), $ravenDocuments),
                array_map(fn(?RavenProxy $ravenProxy) => $ravenProxy?->toDriverArray(), $ravenDocuments),
            );
        }

        return [];
    }

    /**
     * @return array<int, string>
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverReadAllKeys(string $pattern = ''): iterable
    {
        $keys = [];
        if (empty($this->documentPrefix)) {
            throw new PhpfastcacheUnsupportedMethodException('A document prefix must be configured and not empty to be able to load all items from Raven.');
        }
        $results = $this->instance->loadStartingWith(
            RavenProxy::class,
            $this->documentPrefix,
            $pattern,
            0,
            ExtendedCacheItemPoolInterface::MAX_ALL_KEYS_COUNT
        );

        if (is_iterable($results)) {
            /** @var RavenProxy $item */
            foreach (iterator_to_array($results) as $item) {
                $keys[] = $item->getKey();
            }
        }

        return $keys;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        if (($ravenDocument = $this->getRavenDocument($item)) === null) {
            $ravenDocument = new RavenProxy(
                $item,
                $this->getConfig()->isSerializeData(),
                $this->getConfig()->isItemDetailedDate()
            );
        } else {
            $ravenDocument->fromCacheItem($item);
        }

        $this->instance->store(
            $ravenDocument,
            $this->documentPrefix . $item->getKey()
        );

        $this->instance->saveChanges();

        return true;
    }

    /**
     * @param string $key
     * @param string $encodedKey
     * @return bool
     */
    protected function driverDelete(string $key, string $encodedKey): bool
    {
        $this->instance->delete($this->documentPrefix . $key);
        $this->instance->saveChanges();
        return true;
    }

    /**
     * @param string[] $keys
     * @return bool
     */
    protected function driverDeleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->instance->delete($this->documentPrefix . $key);
        }
        $this->instance->saveChanges();

        return true;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $this->documentStorage->operations()->send(
            new DeleteByQueryOperation(new IndexQuery(sprintf('from %s', $this->getCollectionName())))
        );

        $this->instance->clear();
        return true;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $nodes = $this->instance->getRequestExecutor()->getTopology()->getNodes();
        /** @var BuildNumber|null $buildNumber */
        $buildNumber = $this->documentStorage->maintenance()->server()->send(new GetBuildNumberOperation());
        /** @var CollectionStatistics $collectionStats */
        $collectionStats = $this->documentStorage->maintenance()->send(new GetCollectionStatisticsOperation());
        /** @var DetailedDatabaseStatistics $databaseStats */
        $databaseStats = $this->documentStorage->maintenance()->send(new GetDetailedStatisticsOperation());

        $driverStats = (new DriverStatistic())
            ->setRawData(compact('nodes', 'buildNumber', 'collectionStats', 'databaseStats'))
            ->setInfo(
                sprintf(
                    'Ravendb server v%s (%s), client v%s with %s node%s configured: %s. Database/Collection: "%s"/"%s".',
                    $buildNumber?->getFullVersion() ?? 'Unknown version',
                    $buildNumber?->getCommitHash() ?? '********',
                    InstalledVersions::getPrettyVersion('ravendb/ravendb-php-client'),
                    count($nodes),
                    count($nodes) !== 1 ? 's' : '',
                    implode(', ', array_map(
                        fn(ServerNode $node) => 'Node #' . $node->getClusterTag() . ' (' . $node->getServerRole()->getValue() . ') @ ' . $node->getUrl()->getValue(),
                        iterator_to_array($nodes)
                    )),
                    $this->getDatabaseName(),
                    $this->getCollectionName(),
                )
            );

        if (method_exists($driverStats, 'setCount')) {
            $driverStats->setCount(
                $collectionStats->getCollections()[$this->getCollectionName()] ?? $collectionStats->getCountOfDocuments()
            );
        }

        return $driverStats;
    }

    /**
     * @return string
     */
    protected function getDatabaseName(): string
    {
        return $this->getConfig()->getDatabaseName() ?: static::RAVENDB_DEFAULT_DB_NAME;
    }

    /**
     * @return string
     */
    protected function getCollectionName(): string
    {
        return $this->getConfig()->getCollectionName() ?: static::RAVENDB_DEFAULT_COLLECTION_NAME;
    }
}
