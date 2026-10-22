<?php

declare(strict_types=1);

namespace PactivaSign\Key;

/**
 * Something that can sign with a private key: a local key file, an environment variable, or a
 * key held in a KMS or vault that never leaves it (see CallbackKey).
 *
 * The SDK uses two separate providers: one for the client assertion (its public key is registered
 * with PactivaSign) and one for DPoP proofs (never registered, but must stay the same across
 * requests and PHP workers, because access tokens are bound to its thumbprint).
 */
interface KeyProvider
{
    /** JWS algorithm, e.g. ES256, PS256, RS256 or EdDSA. */
    public function algorithm(): string;

    /** Key ID sent in the client assertion header, or null to let the server pick the only active key. */
    public function keyId(): ?string;

    /**
     * Public JWK members (kty, crv, x, y for EC and OKP; kty, n, e for RSA). Never private material.
     *
     * @return array<string, string>
     */
    public function publicJwk(): array;

    /** JWS signature over `$signingInput`, in JOSE format (raw R||S for ECDSA, not DER). */
    public function sign(string $signingInput): string;
}
