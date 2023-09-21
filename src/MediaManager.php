<?php

namespace Audentio\MediaManager;

use Audentio\MediaManager\Exceptions\UrlMatchException;
use Audentio\MediaManager\Providers\AbstractProvider;
use Audentio\MediaManager\Providers\VimeoProvider;
use Audentio\MediaManager\Providers\YoutubeProvider;

class MediaManager
{
    private array $providers = [
        YoutubeProvider::class,
        VimeoProvider::class,
    ];
    private array $config = [];
    private bool $usingProviderWhitelist = false;
    private bool $isSilent = false;

    public function getConfig(string $provider): array
    {
        return $this->config[$provider] ?? [];
    }

    public function get(string $url): ?Media
    {
        foreach ($this->providers as $provider) {
            try {
                /** @var AbstractProvider $handler */
                $handler = new $provider($this, $url);
                if (!$handler->exists()) {
                    return null;
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

    public function usingProviderWhitelist(array $providers): self
    {
        $this->providers = $providers;
        $this->usingProviderWhitelist = true;

        return $this;
    }

    public function __construct(array $additionalProviders = [])
    {
        foreach ($additionalProviders as $provider) {
            $this->providers[] = $provider;
        }
    }
}