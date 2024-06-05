<?php

declare(strict_types=1);

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\Caches\CacheTypeEnum;
use Audentio\MediaManager\Exceptions\ConfigurationException;
use Audentio\MediaManager\Exceptions\UrlMatchException;
use Audentio\MediaManager\MediaBroadcastDetails;
use Audentio\MediaManager\MediaTypeEnum;
use Audentio\MediaManager\Response;
use Audentio\MediaManager\MediaManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

abstract class AbstractProvider
{
    protected MediaManager $mediaManager;
    protected string $url;
    protected array $requestCache = [];
    protected Client $client;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getBroadcastDetails(): ?MediaBroadcastDetails
    {
        return null;
    }

    protected function validateConfig(): void
    {
        foreach ($this->getRequiredConfigKeys() as $key) {
            if (!array_key_exists($key, $this->getConfig())) {
                throw new ConfigurationException('Missing required config key for ' . self::class . ': ' . $key);
            }
        }
    }

    protected function getRequiredConfigKeys(): array
    {
        return [];
    }

    protected function getConfig(): array
    {
        return $this->mediaManager->getConfig(static::class);
    }

    protected function getDurationFromSeconds(int $seconds): \DateInterval
    {
        $duration = 'PT';

        $units = [
            'D' => 60 * 60 * 24,
            'H' => 60 * 60,
            'M' => 60,
            'S' => 1,
        ];

        foreach ($units as $key => $unit) {
            if ($seconds >= $unit) {
                $value = floor($seconds / $unit);
                $seconds -= $value * $unit;
                $duration .= $value . $key;
            }
        }

        return new \DateInterval($duration);
    }

    protected function request(string $url, array $params = [], string $method = 'get', bool $cacheable = true, bool $force = false): ?Response
    {
        $cache = $this->mediaManager->getCache(CacheTypeEnum::REQUEST);
        $requestHash = 'request_' . md5($url);
        if ($cacheable && !$force && $cache->exists($requestHash)) {
            return $cache->get($requestHash);
        }

        try {
            if (!isset($params['headers'])) {
                $params['headers'] = [];
            }

            $params['headers'] = array_replace($params['headers'], $this->getRequestHeaders());
            $response = $this->getHttpClient()->request($method, $url, $params);
            $cacheValue = Response::createFromResponse($response);

            if ($cacheable) {
                $cache->set($requestHash, $cacheValue, 30);
            }
            return $cacheValue;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }

    protected function getHttpClient(): Client
    {
        if (!isset($this->client)) {
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'PHP-MediaManager/0.0'
                ],
            ]);
        }

        return $this->client;
    }

    protected function getRequestHeaders(): array
    {
        return array_replace([], $this->getAdditionalRequestHeaders());
    }

    protected function getAdditionalRequestHeaders(): array
    {
        return [];
    }

    abstract public function getId(): string;
    abstract public function getType(): MediaTypeEnum;
    abstract public function getName(): ?string;
    abstract public function getDescription(): ?string;
    abstract public function getDuration(): ?\DateInterval;
    abstract public function getThumbnail(): ?string;
    abstract public function exists(): bool;
    abstract protected function matchesUrl(): bool;

    public function __construct(MediaManager $MediaManager, string $url)
    {
        $this->mediaManager = $MediaManager;
        $this->url = $url;

        if (!$this->matchesUrl()) {
            throw new UrlMatchException();
        }

        $this->validateConfig();
    }
}
