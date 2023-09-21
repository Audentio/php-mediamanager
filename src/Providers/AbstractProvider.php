<?php

namespace Audentio\MediaManager\Providers;

use Audentio\MediaManager\Exceptions\ConfigurationException;
use Audentio\MediaManager\Exceptions\UrlMatchException;
use Audentio\MediaManager\Response;
use Audentio\MediaManager\MediaManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

abstract class AbstractProvider
{
    protected MediaManager $MediaManager;
    protected string $url;
    protected array $requestCache = [];
    protected Client $client;

    public function getUrl(): string
    {
        return $this->url;
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
        return $this->MediaManager->getConfig(static::class);
    }

    protected function request(string $url, bool $force = false): ?Response
    {
        $requestHash = $url;
        if (!$force && array_key_exists($requestHash, $this->requestCache)) {
            return $this->requestCache[$requestHash];
        }
        if (!isset($this->client)) {
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'PHP-MediaManager/0.0'
                ],
            ]);
        }


        try {
            $response = $this->client->request('get', $url, $this->getRequestHeaders());

            $this->requestCache[$requestHash] = new Response($response);
            return $this->requestCache[$requestHash];
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404 || $e->getResponse()->getStatusCode() === 400) {
                return null;
            }

            throw $e;
        }
    }

    protected function getRequestHeaders(): array
    {
        return array_replace([], $this->_getRequestHeaders());
    }

    protected function _getRequestHeaders(): array
    {
        return [];
    }

    abstract public function getId(): string;
    abstract public function getName(): ?string;
    abstract public function getDescription(): ?string;
    abstract public function getDuration(): \DateInterval;
    abstract public function getThumbnail(): ?string;
    abstract public function exists(): bool;
    abstract protected function matchesUrl(): bool;

    public function __construct(MediaManager $MediaManager, string $url)
    {
        $this->MediaManager = $MediaManager;
        $this->url = $url;

        if (!$this->matchesUrl()) {
            throw new UrlMatchException;
        }

        $this->validateConfig();
    }
}