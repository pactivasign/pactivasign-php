<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

/**
 * A lock shared by all PHP workers, so only one of them requests a new access token while the
 * others wait and then read it from the cache. The Laravel package backs this with Cache::lock.
 */
interface TokenLock
{
    /** Takes the lock if nobody holds it. It must expire on its own after `$ttlSeconds`. */
    public function acquire(string $key, int $ttlSeconds): bool;

    public function release(string $key): void;
}
