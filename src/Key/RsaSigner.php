<?php

declare(strict_types=1);

namespace PactivaSign\Key;

use PactivaSign\Exception\ConfigurationException;

/**
 * RSA signing through OpenSSL.
 *
 * The JWT library signs RSA in pure PHP, which takes seconds (RS256) to minutes (PS256) without the
 * gmp extension. OpenSSL does RS* natively. For PS* the EMSA-PSS encoding (RFC 8017 §9.1.1, salt
 * length = hash length as RFC 7518 requires) is built here and only the RSA operation runs in OpenSSL.
 *
 * @internal
 */
final class RsaSigner
{
    private const HASHES = [
        'RS256' => 'sha256', 'RS384' => 'sha384', 'RS512' => 'sha512',
        'PS256' => 'sha256', 'PS384' => 'sha384', 'PS512' => 'sha512',
    ];

    private readonly \OpenSSLAsymmetricKey $key;
    private readonly int $modulusBits;

    public function __construct(string $privateKeyPem)
    {
        $key = openssl_pkey_get_private($privateKeyPem);
        $details = $key === false ? false : openssl_pkey_get_details($key);
        if ($key === false || $details === false || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new ConfigurationException('The RSA private key could not be loaded.');
        }
        $this->key = $key;
        $this->modulusBits = (int) $details['bits'];
    }

    /**
     * PKCS#1 PEM from the JWK's private members. Building it here avoids the JWT library's
     * pure-PHP conversion, which takes seconds per key load.
     *
     * @param array<string, mixed> $jwk
     */
    public static function fromJwk(array $jwk): self
    {
        $members = ['n', 'e', 'd', 'p', 'q', 'dp', 'dq', 'qi'];
        $integers = [self::derInteger("\0")];
        foreach ($members as $member) {
            if (!is_string($jwk[$member] ?? null)) {
                throw new ConfigurationException(sprintf('The RSA JWK is missing "%s".', $member));
            }
            $integers[] = self::derInteger((string) base64_decode(strtr($jwk[$member], '-_', '+/'), true));
        }
        $der = "\x30" . self::derLength(strlen(implode('', $integers))) . implode('', $integers);

        return new self("-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END RSA PRIVATE KEY-----\n");
    }

    public function sign(string $algorithm, string $input): string
    {
        $hash = self::HASHES[$algorithm] ?? throw new ConfigurationException(sprintf('"%s" is not an RSA algorithm.', $algorithm));

        if ($algorithm[0] === 'R') {
            if (!openssl_sign($input, $signature, $this->key, $hash)) {
                throw new \RuntimeException('OpenSSL could not sign with the RSA key.');
            }

            return (string) $signature;
        }

        $modulusBytes = intdiv($this->modulusBits + 7, 8);
        $encoded = self::emsaPssEncode($input, $this->modulusBits - 1, $hash);
        $block = str_pad($encoded, $modulusBytes, "\0", STR_PAD_LEFT);
        if (!openssl_private_encrypt($block, $signature, $this->key, OPENSSL_NO_PADDING)) {
            throw new \RuntimeException('OpenSSL could not sign with the RSA key.');
        }

        return (string) $signature;
    }

    private static function emsaPssEncode(string $message, int $emBits, string $hash): string
    {
        $hLen = strlen(hash($hash, '', true));
        $sLen = $hLen;
        $emLen = intdiv($emBits + 7, 8);
        if ($emLen < $hLen + $sLen + 2) {
            throw new ConfigurationException('The RSA key is too small for this algorithm.');
        }

        $salt = random_bytes($sLen);
        $h = hash($hash, str_repeat("\0", 8) . hash($hash, $message, true) . $salt, true);
        $db = str_repeat("\0", $emLen - $sLen - $hLen - 2) . "\x01" . $salt;
        $maskedDb = $db ^ self::mgf1($h, $emLen - $hLen - 1, $hash);
        $maskedDb[0] = chr(ord($maskedDb[0]) & (0xff >> (8 * $emLen - $emBits)));

        return $maskedDb . $h . "\xbc";
    }

    private static function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\0");
        if ($bytes === '' || ord($bytes[0]) & 0x80) {
            $bytes = "\0" . $bytes;
        }

        return "\x02" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = ltrim(pack('N', $length), "\0");

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function mgf1(string $seed, int $length, string $hash): string
    {
        $mask = '';
        for ($counter = 0; strlen($mask) < $length; $counter++) {
            $mask .= hash($hash, $seed . pack('N', $counter), true);
        }

        return substr($mask, 0, $length);
    }
}
