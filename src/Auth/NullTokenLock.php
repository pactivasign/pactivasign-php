<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

/**
 * Default lock that always succeeds: each worker fetches its own token when the cache is empty.
 * Correct, just not as economical as a shared lock under heavy concurrency.
 */
final class NullTokenLock implements TokenLock
{
    public function acquire(string $key, int $ttlSeconds): bool
    {
        return true;
    }

    public function release(string $key): void
    {
    }
}
