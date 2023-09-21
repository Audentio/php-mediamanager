<?php

namespace Audentio\MediaManager;

use Psr\Http\Message\ResponseInterface;

class Response
{
    private ResponseInterface $response;

    private string $contents;

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
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

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }
}