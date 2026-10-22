<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

use PactivaSign\Key\KeyProvider;
use Psr\Clock\ClockInterface;

/**
 * Builds the `private_key_jwt` client assertion (RFC 7523): iss = sub = client ID, a single
 * audience, a 60-second lifetime and a fresh `jti`, so each assertion can be used once.
 *
 * @internal
 */
final class ClientAssertionFactory
{
    public const LIFETIME_SECONDS = 60;

    public function __construct(
        private readonly string $clientId,
        private readonly KeyProvider $key,
        private readonly string $audience,
        private readonly ClockInterface $clock,
    ) {
    }

    public function create(): string
    {
        $now = $this->clock->now()->getTimestamp();
        $header = ['alg' => $this->key->algorithm(), 'typ' => 'JWT'];
        if ($this->key->keyId() !== null) {
            $header['kid'] = $this->key->keyId();
        }

        return Jws::sign($header, [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $this->audience,
            'iat' => $now,
            'exp' => $now + self::LIFETIME_SECONDS,
            'jti' => Jws::uuid(),
        ], $this->key);
    }
}
