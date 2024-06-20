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

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;

/**
 * @internal
 */
class RavenProxy
{
    protected ?string $key;

    protected mixed $data;

    /**
     * @var array<string>
     */
    protected array $tags = [];

    protected \DateTimeInterface $expirationDate;

    protected ?\DateTimeInterface $creationDate = null;

    protected ?\DateTimeInterface $modificationDate = null;

    public function __construct(?ExtendedCacheItemInterface $item = null, protected bool $detailedDate = false)
    {
        if ($item) {
            $this->fromCacheItem($item);
        }
    }

    public function getKey(): ?string
    {
        return $this->key ?? null;
    }

    public function setKey(string $key): self
    {
        if (isset($this->key)) {
            throw new PhpfastcacheInvalidArgumentException('Cannot change key');
        }

        $this->key = $key;
        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array<string> $tags
     * @return $this
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(?\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    public function getModificationDate(): ?\DateTimeInterface
    {
        return $this->modificationDate;
    }

    public function setModificationDate(?\DateTimeInterface $modificationDate): self
    {
        $this->modificationDate = $modificationDate;
        return $this;
    }

    public function fromCacheItem(ExtendedCacheItemInterface $item): void
    {
        if (!isset($this->key)) {
            $this->setKey($item->getKey());
        }

        $this->setData($item->_getData())
        ->setExpirationDate($item->getExpirationDate())
        ->setCreationDate($item->getCreationDate())
        ->setModificationDate($item->getModificationDate())
        ->setTags($item->getTags());

        if ($this->detailedDate) {
            $this->setModificationDate($item->getModificationDate())
                ->setCreationDate($item->getCreationDate());
        }
    }

    /**
     * @return null|array<string, mixed>
     */
    public function toDriverArray(): ?array
    {
        if ($this->key) {
            return [
                ExtendedCacheItemPoolInterface::DRIVER_KEY_WRAPPER_INDEX => $this->key,
                ExtendedCacheItemPoolInterface::DRIVER_DATA_WRAPPER_INDEX => $this->data,
                ExtendedCacheItemPoolInterface::DRIVER_EDATE_WRAPPER_INDEX => $this->expirationDate,
                ExtendedCacheItemPoolInterface::DRIVER_CDATE_WRAPPER_INDEX => $this->creationDate,
                ExtendedCacheItemPoolInterface::DRIVER_MDATE_WRAPPER_INDEX => $this->modificationDate,
                TaggableCacheItemPoolInterface::DRIVER_TAGS_WRAPPER_INDEX => $this->tags,
            ];
        }
        return null;
    }

    public function setDetailedDate(bool $detailedDate): void
    {
        $this->detailedDate = $detailedDate;
    }
}
