<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

use PactivaSign\Key\Jwk;
use PactivaSign\Key\KeyProvider;
use Psr\Clock\ClockInterface;

/**
 * Builds DPoP proofs (RFC 9449). Every HTTP request, including each retry, gets its own proof,
 * because the server accepts each proof `jti` only once.
 *
 * @internal
 */
final class DpopProofFactory
{
    /** @var array<string, string> */
    private readonly array $publicJwk;

    public function __construct(
        private readonly KeyProvider $key,
        private readonly ClockInterface $clock,
    ) {
        $this->publicJwk = Jwk::publicMembers($key->publicJwk());
    }

    /** Thumbprint of the DPoP key; access tokens are bound to it (`cnf.jkt`). */
    public function thumbprint(): string
    {
        return Jwk::thumbprint($this->publicJwk);
    }

    public function create(string $method, string $url, ?string $accessToken = null, ?string $nonce = null): string
    {
        $claims = [
            'jti' => Jws::uuid(),
            'htm' => strtoupper($method),
            'htu' => self::htu($url),
            'iat' => $this->clock->now()->getTimestamp(),
        ];
        if ($accessToken !== null) {
            $claims['ath'] = Jws::base64Url(hash('sha256', $accessToken, true));
        }
        if ($nonce !== null) {
            $claims['nonce'] = $nonce;
        }

        return Jws::sign(
            ['typ' => 'dpop+jwt', 'alg' => $this->key->algorithm(), 'jwk' => $this->publicJwk],
            $claims,
            $this->key,
        );
    }

    /** The request URL without query and fragment (RFC 9449 §4.2). */
    public static function htu(string $url): string
    {
        $cut = strcspn($url, '?#');

        return substr($url, 0, $cut);
    }
}
