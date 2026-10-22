<?php

declare(strict_types=1);

namespace PactivaSign\Http;

/**
 * When and how long to wait before retrying a request.
 *
 * Only safe requests are retried: GET and HEAD, and requests sent with an Idempotency-Key (the
 * server then runs the operation at most once). Anything else fails straight away and the caller
 * decides, so a POST can never create something twice.
 */
final class RetryPolicy
{
    public const RETRY_STATUSES = [429, 502, 503, 504];

    /**
     * @param int $maxAttempts total attempts, including the first
     * @param int $baseDelayMs first backoff; doubles each attempt, with full jitter
     * @param int $maxDelayMs cap on a single backoff
     * @param int $maxRetryAfterSeconds a longer Retry-After is not waited on; the error is thrown
     */
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly int $baseDelayMs = 500,
        public readonly int $maxDelayMs = 8_000,
        public readonly int $maxRetryAfterSeconds = 60,
    ) {
    }

    public static function none(): self
    {
        return new self(maxAttempts: 1);
    }

    /** Milliseconds to wait before attempt `$attempt + 1`, or null to stop retrying. */
    public function delayMs(int $attempt, ?int $retryAfterSeconds): ?int
    {
        if ($attempt >= $this->maxAttempts) {
            return null;
        }
        if ($retryAfterSeconds !== null) {
            return $retryAfterSeconds > $this->maxRetryAfterSeconds ? null : $retryAfterSeconds * 1000;
        }
        $ceiling = min($this->maxDelayMs, $this->baseDelayMs * (2 ** ($attempt - 1)));

        return random_int(0, $ceiling);
    }
}
