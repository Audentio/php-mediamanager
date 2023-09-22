<?php

declare(strict_types=1);

namespace Audentio\MediaManager;

use Audentio\MediaManager\Caches\CacheTypeEnum;
use Audentio\MediaManager\Caches\Handlers\AbstractCache;
use Audentio\MediaManager\Caches\Handlers\StaticCache;
use Audentio\MediaManager\Exceptions\NotFoundException;
use Audentio\MediaManager\Exceptions\ProviderNotFoundException;
use Audentio\MediaManager\Exceptions\UrlMatchException;
use Audentio\MediaManager\Providers\AbstractProvider;
use Audentio\MediaManager\Providers\ApplePodcastProvider;
use Audentio\MediaManager\Providers\SpotifyProvider;
use Audentio\MediaManager\Providers\VimeoProvider;
use Audentio\MediaManager\Providers\YoutubeProvider;

class MediaManager
{
    private array $providers = [
        YoutubeProvider::class,
        VimeoProvider::class,
        ApplePodcastProvider::class,
        SpotifyProvider::class,
    ];
    private array $config = [];
    private bool $usingProviderWhitelist = false;
    private bool $isSilent = false;
    private array $caches;

    public function getConfig(string $provider): array
    {
        return $this->config[$provider] ?? [];
    }

    public function getCache(CacheTypeEnum $cacheType): AbstractCache
    {
        return $this->caches[$cacheType->name];
    }

    public function get(string $url): ?Media
    {
        foreach ($this->providers as $provider) {
            try {
                /** @var AbstractProvider $handler */
                $handler = new $provider($this, $url);
                if (!$handler->exists()) {
                    throw new NotFoundException;
                }

                return new Media($handler);
            } catch (UrlMatchException $e) {
                continue;
            } catch (\Throwable $e) {
                if (!$this->isSilent) {
                    throw $e;
                }

                return null;
            }
        }

        if (!$this->isSilent) {
            throw new ProviderNotFoundException;
        }

        return null;
    }

    public function configure(string $provider, array $config = []): self
    {
        if (!in_array($provider, $this->providers)) {
            if ($this->usingProviderWhitelist) {
                return $this;
            }
            $this->providers[] = $provider;
        }

        $this->config[$provider] = $config;

        return $this;
    }

    public function silence(bool $isSilent = true): self
    {
        $this->isSilent = $isSilent;

        return $this;
    }

    public function withAdditionalProviders(array $providers): self
    {
        foreach ($providers as $provider) {
            if (!in_array($provider, $this->providers)) {
                $this->providers[] = $provider;
            }
        }

        return $this;
    }

    public function usingProviderWhitelist(array $providers): self
    {
        $this->providers = $providers;
        $this->usingProviderWhitelist = true;

        return $this;
    }

    public function __construct(null|AbstractCache|array $caches = null)
    {
        if ($caches === null) {
            $caches = new StaticCache;
        }

        if (!is_array($caches)) {
            $cache = $caches;
            $caches = [];

            foreach (CacheTypeEnum::cases() as $case) {
                $caches[$case->name] = $cache;
            }
        }

        foreach (CacheTypeEnum::cases() as $case) {
            $defaultCache = null;
            if (!array_key_exists($case->name, $caches)) {
                if (!$defaultCache) {
                    $defaultCache = new StaticCache;
                }
                $caches[$case->name] = $defaultCache;
            }
        }

        $this->caches = $caches;
    }
}
