<?php

namespace Audentio\MediaManager\Caches;

interface CacheDataInterface
{
    public function getCacheData(): mixed;
    public static function createFromCacheData(mixed $data): static;
}