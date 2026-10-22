<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

use Psr\Http\Message\ResponseInterface;

/** Too many requests (429). `retryAfter` is the server's Retry-After in seconds, when it sent one. */
final class RateLimitException extends ApiException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message,
        int $statusCode,
        string $errorCode,
        ?string $requestId = null,
        array $details = [],
        ?ResponseInterface $response = null,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusCode, $errorCode, $requestId, $details, $response);
    }
}
