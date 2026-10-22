<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use Jose\Component\KeyManagement\JWKFactory;
use PactivaSign\Auth\Jws;
use PactivaSign\Key\LocalKey;
use PactivaSign\Tests\Support\JwtAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RsaSignerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int}>
     */
    public static function keys(): iterable
    {
        foreach (['PS256', 'PS384', 'PS512', 'RS256', 'RS384', 'RS512'] as $algorithm) {
            // 2049 bits: the PSS encoding is one byte shorter than the modulus and must be padded.
            foreach ([2048, 2049, 3072] as $bits) {
                yield "$algorithm/$bits" => [$algorithm, $bits];
            }
        }
    }

    /**
     * Verified with the openssl command-line tool: the JWT library's own PSS verifier rejects
     * valid signatures from keys whose size is not a multiple of 8 bits.
     */
    #[DataProvider('keys')]
    public function testSignaturesVerifyWithOpenSslCli(string $algorithm, int $bits): void
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => $bits]);
        self::assertNotFalse($resource);
        $details = openssl_pkey_get_details($resource);
        self::assertIsArray($details);
        openssl_pkey_export($resource, $pem);
        $key = LocalKey::fromJwk(JWKFactory::createFromKey((string) $pem)->all(), $algorithm);

        $dir = sys_get_temp_dir() . '/pactivasign-rsa-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents("$dir/pub.pem", (string) $details['key']);
        file_put_contents("$dir/msg", 'signing input');
        file_put_contents("$dir/sig", $key->sign('signing input'));
        $digest = 'sha' . substr($algorithm, 2);
        $pss = $algorithm[0] === 'P'
            ? sprintf('-sigopt rsa_padding_mode:pss -sigopt rsa_pss_saltlen:%d', intdiv((int) substr($algorithm, 2), 8))
            : '';

        exec(sprintf('openssl dgst -%s %s -verify %s -signature %s %s 2>&1', $digest, $pss, "$dir/pub.pem", "$dir/sig", "$dir/msg"), $output, $exit);
        array_map('unlink', glob("$dir/*") ?: []);
        rmdir($dir);

        self::assertSame(0, $exit, implode("\n", $output));
    }

    public function testJwsFromRsaKeysVerifiesWithTheJwtLibrary(): void
    {
        foreach (['PS256', 'RS256'] as $algorithm) {
            $key = LocalKey::generate($algorithm);
            JwtAssert::verified(Jws::sign(['alg' => $algorithm], ['x' => 1], $key), $key->publicJwk());
        }
    }

    public function testRsaSignaturesMatchOpenSsl(): void
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($resource);
        $details = openssl_pkey_get_details($resource);
        self::assertIsArray($details);
        openssl_pkey_export($resource, $pem);
        $key = LocalKey::fromJwk(JWKFactory::createFromKey((string) $pem)->all(), 'RS256');

        $signature = $key->sign('payload');

        self::assertSame(1, openssl_verify('payload', $signature, (string) $details['key'], OPENSSL_ALGO_SHA256));
        self::assertNotSame('', Jws::base64Url($signature));
    }
}
