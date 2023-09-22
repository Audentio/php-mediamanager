<?php

namespace Audentio\MediaManager\Caches\Handlers;

use Audentio\MediaManager\Caches\CacheDataInterface;
use Audentio\MediaManager\Exceptions\UncacheableDataException;

abstract class AbstractCache
{
    public function exists(string $key): bool
    {
        return $this->keyExistsInCache($key);
    }

    public function get(string $key): mixed
    {
        $data = $this->getValueFromCache($key);

        if (isset($data['isCacheableClass'])) {
            $class = $data['class'];
            $data = $data['data'];

            return $class::createFromCacheData($data);
        }

        return $data;
    }

    public function set(string $key, mixed $value, \DateTimeInterface|\DateInterval|int $ttl = null): void
    {
        $isClass = false;
        if (is_object($value)) {
            $isClass = get_class($value) !== null;
        }

        if ($isClass) {
            if (!$value instanceof CacheDataInterface) {
                throw new UncacheableDataException('Cannot cache data of type ' . get_class($value) . ' as it does not implement CacheDataInterface');
            }

            $value = [
                'isCacheableClass' => true,
                'class' => get_class($value),
                'data' => $value->getCacheData(),
            ];
        }
        $expires = null;
        if ($ttl) {
            if ($ttl instanceof \DateTimeInterface) {
                $expires = new $ttl;
            } else {
                $expires = $this->getExpirationForTtl($ttl);
            }
        }

        $this->setValueInCache($key, $value, $expires);
    }

    abstract protected function keyExistsInCache(string $key): bool;
    abstract protected function getValueFromCache(string $key): mixed;
    abstract protected function setValueInCache(string $key, mixed $value, ?\DateTimeInterface $expires): void;

    protected function getExpirationForTtl(\DateInterval|int $ttl): ?\DateTime
    {
        if ($ttl instanceof \DateInterval) {
            $referenceDate = new \DateTimeImmutable;
            $endReferenceDate = $referenceDate->add($ttl);
            $ttl = $endReferenceDate->getTimestamp() - $referenceDate->getTimestamp();
        }

        return $ttl ? (new \DateTime())->setTimestamp(time() + $ttl) : null;
    }
}