<?php

namespace Audentio\MediaManager\Caches\Handlers;

class StaticCache extends AbstractCache
{
    private static array $cache = [];

    protected function keyExistsInCache(string $key): bool
    {
        if (!array_key_exists($key, static::$cache)) {
            return false;
        }

        $cache = static::$cache[$key];

        if ($cache['expires'] && $cache['expires'] < time()) {
            unset(static::$cache[$key]);
            return false;
        }

        return true;
    }

    protected function getValueFromCache(string $key): mixed
    {
        if (!$this->exists($key)) {
            return null;
        }

        return static::$cache[$key]['value'];
    }

    protected function setValueInCache(string $key, mixed $value, ?\DateTimeInterface $expires): void
    {
        static::$cache[$key] = [
            'expires' => $expires?->getTimestamp(),
            'value' => $value,
        ];
    }

}