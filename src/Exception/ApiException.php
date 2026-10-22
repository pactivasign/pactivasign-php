<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * The API answered with an error.
 *
 * `errorCode` is the API's closed error code (for example `NOT_FOUND`) or, for OAuth failures,
 * the RFC 6749 / RFC 9449 code (for example `invalid_token`). New codes can appear in minor
 * API versions, so treat unknown ones like the parent class.
 */
class ApiException extends \RuntimeException implements PactivaException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $errorCode,
        public readonly ?string $requestId = null,
        public readonly array $details = [],
        public readonly ?ResponseInterface $response = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $requestId === null ? $message : sprintf('%s (request ID %s)', $message, $requestId),
            $statusCode,
            $previous,
        );
    }
}
