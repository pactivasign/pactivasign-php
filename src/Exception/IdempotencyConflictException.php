<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/**
 * The Idempotency-Key was already used for a different request (`IDEMPOTENCY_KEY_REUSED`), or the
 * original request is still running (`IDEMPOTENCY_IN_PROGRESS`) after the SDK stopped waiting.
 */
final class IdempotencyConflictException extends ConflictException
{
}
