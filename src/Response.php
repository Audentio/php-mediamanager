<?php

declare(strict_types=1);

namespace Audentio\MediaManager;

use Audentio\MediaManager\Caches\CacheDataInterface;
use Psr\Http\Message\ResponseInterface;

class Response implements CacheDataInterface
{
    private array $headers;
    private int $statusCode;
    private string $contents;

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContents(): string
    {
        if (!isset($this->contents)) {
            $body = $this->response->getBody();
            $body->rewind();

            $this->contents = $body->getContents();
        }

        return $this->contents;
    }

    public function getCacheData(): mixed
    {
        return [
            'headers' => $this->headers,
            'statusCode' => $this->statusCode,
            'contents' => $this->contents,
        ];
    }

    public static function createFromCacheData(mixed $data): static
    {
        return new static(
            $data['headers'],
            $data['statusCode'],
            $data['contents']
        );
    }

    public static function createFromResponse(ResponseInterface $response): static
    {
        $response->getBody()->rewind();
        return new static(
            $response->getHeaders(),
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );
    }

    public function __construct(array $headers, int $statusCode, string $contents)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $this->contents = $contents;
    }
}
