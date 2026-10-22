<?php

declare(strict_types=1);

namespace PactivaSign\Cache;

use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * In-process PSR-16 cache, used when no shared cache is configured. Tokens then live for one PHP
 * request (or one long-running worker); pass your framework's cache to share them across workers.
 *
 * @internal
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string, array{mixed, ?int}> */
    private array $items = [];

    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->items[$key])) {
            return $default;
        }
        [$value, $expiresAt] = $this->items[$key];
        if ($expiresAt !== null && $expiresAt <= $this->clock->now()->getTimestamp()) {
            unset($this->items[$key]);

            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        if ($ttl instanceof \DateInterval) {
            $ttl = (new \DateTimeImmutable('@0'))->add($ttl)->getTimestamp();
        }
        if ($ttl !== null && $ttl <= 0) {
            return $this->delete($key);
        }
        $this->items[$key] = [$value, $ttl === null ? null : $this->clock->now()->getTimestamp() + $ttl];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }
}
