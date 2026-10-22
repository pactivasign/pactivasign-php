<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

use Psr\SimpleCache\CacheInterface;

/**
 * Latest DPoP nonce per server (token endpoint and API), shared through the cache so a nonce one
 * worker received saves the others a challenge round trip.
 *
 * @internal
 */
final class NonceStore
{
    public const TOKEN_ENDPOINT = 'token';
    public const API = 'api';

    private const TTL_SECONDS = 300;

    /** @var array<string, string> */
    private array $local = [];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $prefix,
    ) {
    }

    public function get(string $server): ?string
    {
        if (isset($this->local[$server])) {
            return $this->local[$server];
        }
        $nonce = $this->cache->get($this->key($server));

        return is_string($nonce) && $nonce !== '' ? $this->local[$server] = $nonce : null;
    }

    public function remember(string $server, string $nonce): void
    {
        if ($nonce === '' || ($this->local[$server] ?? null) === $nonce) {
            return;
        }
        $this->local[$server] = $nonce;
        $this->cache->set($this->key($server), $nonce, self::TTL_SECONDS);
    }

    private function key(string $server): string
    {
        return $this->prefix . '.nonce.' . $server;
    }
}
