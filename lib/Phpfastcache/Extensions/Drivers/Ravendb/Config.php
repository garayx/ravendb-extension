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

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use RavenDB\Auth\AuthOptions;

class Config extends ConfigurationOption
{
    /**
     * @var array<string>
     */
    protected array $hosts = [];
    protected string $username = '';
    protected string $password = '';
    protected bool $serializeData = false;
    protected ?AuthOptions $authOptions = null;
    protected string $databaseName = Driver::RAVENDB_DEFAULT_DB_NAME;
    protected string $collectionName = Driver::RAVENDB_DEFAULT_COLLECTION_NAME;
    protected string $documentPrefix = Driver::RAVENDB_DOCUMENT_PREFIX;

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * @param string $databaseName
     * @return Config
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setDatabaseName(string $databaseName): static
    {
        if (\preg_match('#^[A-z][A-z0-9-.]+$#', $databaseName)) {
            return $this->setProperty('databaseName', $databaseName);
        }

        throw new PhpfastcacheInvalidArgumentException(sprintf("Error: Illegal database name: '%s'. 
            Only lowercase characters (a-z), digits (0-9), and any of the characters _-. are allowed. Must begin with a letter.", $databaseName));
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return Config
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): static
    {
        if (\preg_match('#^[A-z][A-z0-9-.]+$#', $collectionName)) {
            return $this->setProperty('collectionName', $collectionName);
        }

        throw new PhpfastcacheInvalidArgumentException(sprintf("Error: Illegal collection name: '%s'. 
            Only lowercase characters (a-z), digits (0-9), and any of the characters _-. are allowed. Must begin with a letter.", $collectionName));
    }

    /**
     * @return string
     */
    public function getDocumentPrefix(): string
    {
        return $this->documentPrefix;
    }

    /**
     * @param string $documentPrefix
     * @return Config
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setDocumentPrefix(string $documentPrefix): static
    {
        if (\preg_match('#^[A-z][A-z0-9-./]+$#', $documentPrefix)) {
            return $this->setProperty('documentPrefix', $documentPrefix);
        }

        throw new PhpfastcacheInvalidArgumentException(sprintf("Error: Illegal document prefix: '%s'. 
            Only lowercase characters (a-z), digits (0-9), and any of the characters _-., and / are allowed. Must begin with a letter.", $documentPrefix));
    }

    /**
     * @return string[]
     */
    public function getHost(): array
    {
        return $this->hosts;
    }

    /**
     * @param string[] $hosts
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setHost(array $hosts): static
    {
        return $this->setProperty('hosts', $hosts);
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): static
    {
        return $this->setProperty('username', $username);
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        return $this->setProperty('password', $password);
    }

    /**
     * @return bool
     */
    public function isSerializeData(): bool
    {
        return $this->serializeData;
    }

    public function getAuthOptions(): ?AuthOptions
    {
        return $this->authOptions;
    }

    public function setAuthOptions(?AuthOptions $authOptions): static
    {
        return $this->setProperty('authOptions', $authOptions);
    }
}
