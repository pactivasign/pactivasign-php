<?php

declare(strict_types=1);

namespace PactivaSign\Key;

use PactivaSign\Exception\ConfigurationException;

/**
 * A key that signs outside this process, e.g. in AWS KMS, Azure Key Vault or HashiCorp Vault.
 *
 * The callback receives the JWS signing input and must return the signature in JOSE format. For
 * ECDSA that is raw R||S (64 bytes for ES256), not the DER encoding most KMS APIs return; convert
 * it with CallbackKey::derToJose().
 */
final class CallbackKey implements KeyProvider
{
    /** @var \Closure(string): string */
    private readonly \Closure $signer;

    /** @var array<string, string> */
    private readonly array $publicJwk;

    /**
     * @param array<string, mixed> $publicJwk
     * @param callable(string): string $signer
     */
    public function __construct(
        private readonly string $algorithm,
        array $publicJwk,
        callable $signer,
        private readonly ?string $keyId = null,
    ) {
        if (!in_array($algorithm, LocalKey::ALGORITHMS, true)) {
            throw new ConfigurationException(sprintf('Algorithm "%s" is not supported.', $algorithm));
        }
        if (isset($publicJwk['d'])) {
            throw new ConfigurationException('CallbackKey takes the public JWK only.');
        }
        $this->publicJwk = Jwk::publicMembers($publicJwk);
        $this->signer = $signer(...);
    }

    public function algorithm(): string
    {
        return $this->algorithm;
    }

    public function keyId(): ?string
    {
        return $this->keyId;
    }

    public function publicJwk(): array
    {
        return $this->publicJwk;
    }

    public function sign(string $signingInput): string
    {
        return ($this->signer)($signingInput);
    }

    /**
     * Converts a DER-encoded ECDSA signature (as returned by KMS and openssl_sign) to JOSE R||S.
     *
     * @param int $partLength 32 for ES256, 48 for ES384, 66 for ES512
     */
    public static function derToJose(string $der, int $partLength): string
    {
        $offset = 0;
        $read = static function (int $expectedTag) use (&$der, &$offset): string {
            if (ord($der[$offset] ?? "\0") !== $expectedTag) {
                throw new ConfigurationException('Signature is not valid DER.');
            }
            $offset++;
            $length = ord($der[$offset++] ?? "\0");
            if ($length & 0x80) {
                $bytes = $length & 0x7f;
                $length = 0;
                for ($i = 0; $i < $bytes; $i++) {
                    $length = ($length << 8) | ord($der[$offset++] ?? "\0");
                }
            }
            $value = substr($der, $offset, $length);
            $offset += $length;

            return $value;
        };

        $sequence = $read(0x30);
        $der = $sequence;
        $offset = 0;
        $r = ltrim($read(0x02), "\0");
        $s = ltrim($read(0x02), "\0");
        if (strlen($r) > $partLength || strlen($s) > $partLength) {
            throw new ConfigurationException('Signature does not fit the curve size.');
        }

        return str_pad($r, $partLength, "\0", STR_PAD_LEFT) . str_pad($s, $partLength, "\0", STR_PAD_LEFT);
    }
}
