<?php

declare(strict_types=1);

namespace PactivaSign\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A successful API response.
 */
final class Response
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function statusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function header(string $name): ?string
    {
        return $this->response->hasHeader($name) ? $this->response->getHeaderLine($name) : null;
    }

    /** The X-Request-Id to quote to PactivaSign support. */
    public function requestId(): ?string
    {
        return $this->header('X-Request-Id');
    }

    /**
     * The decoded JSON body. API responses wrap their payload as `{"success": true, "data": ...}`;
     * use data() for the payload.
     *
     * @return array<mixed>
     */
    public function json(): array
    {
        $body = $this->response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $contents = (string) $body;
        if ($contents === '') {
            return [];
        }
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** The `data` member of the response envelope. */
    public function data(): mixed
    {
        $json = $this->json();

        return array_key_exists('data', $json) ? $json['data'] : $json;
    }

    /** The raw body, e.g. to stream a downloaded PDF to disk. */
    public function body(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function psrResponse(): ResponseInterface
    {
        return $this->response;
    }
}
