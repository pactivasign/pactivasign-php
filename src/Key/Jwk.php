<?php

declare(strict_types=1);

namespace PactivaSign\Key;

use Jose\Component\Core\JWK as JoseJwk;
use PactivaSign\Exception\ConfigurationException;

/**
 * @internal
 */
final class Jwk
{
    private const PUBLIC_MEMBERS = [
        'EC' => ['kty', 'crv', 'x', 'y'],
        'OKP' => ['kty', 'crv', 'x'],
        'RSA' => ['kty', 'n', 'e'],
    ];

    /**
     * The required public members only, so nothing private or decorative leaks into a DPoP header.
     *
     * @param array<string, mixed> $jwk
     * @return array<string, string>
     */
    public static function publicMembers(array $jwk): array
    {
        $kty = is_string($jwk['kty'] ?? null) ? $jwk['kty'] : '';
        $members = self::PUBLIC_MEMBERS[$kty] ?? null;
        if ($members === null) {
            throw new ConfigurationException(sprintf('Unsupported JWK key type "%s".', $kty));
        }
        $public = [];
        foreach ($members as $member) {
            if (!is_string($jwk[$member] ?? null) || $jwk[$member] === '') {
                throw new ConfigurationException(sprintf('JWK is missing "%s".', $member));
            }
            $public[$member] = $jwk[$member];
        }

        return $public;
    }

    /**
     * RFC 7638 SHA-256 thumbprint, base64url. This is the DPoP `jkt` the access token is bound to.
     *
     * @param array<string, string> $publicJwk
     */
    public static function thumbprint(array $publicJwk): string
    {
        return (new JoseJwk(self::publicMembers($publicJwk)))->thumbprint('sha256');
    }
}
