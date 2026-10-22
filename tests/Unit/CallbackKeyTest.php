<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use Jose\Component\KeyManagement\JWKFactory;
use PactivaSign\Auth\Jws;
use PactivaSign\Exception\ConfigurationException;
use PactivaSign\Key\CallbackKey;
use PactivaSign\Tests\Support\JwtAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CallbackKeyTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public static function curves(): iterable
    {
        yield 'ES256' => ['ES256', 'prime256v1', OPENSSL_ALGO_SHA256, 32];
        yield 'ES384' => ['ES384', 'secp384r1', OPENSSL_ALGO_SHA384, 48];
        yield 'ES512' => ['ES512', 'secp521r1', OPENSSL_ALGO_SHA512, 66];
    }

    /**
     * Simulates a KMS: the private key stays inside the callback, which returns DER like most KMS APIs.
     */
    #[DataProvider('curves')]
    public function testSignsThroughACallbackThatReturnsDer(string $algorithm, string $curve, int $digest, int $partLength): void
    {
        $private = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => $curve]);
        self::assertNotFalse($private);
        $details = openssl_pkey_get_details($private);
        self::assertIsArray($details);
        $publicJwk = JWKFactory::createFromKey((string) $details['key'])->all();

        $key = new CallbackKey($algorithm, $publicJwk, static function (string $input) use ($private, $digest, $partLength): string {
            openssl_sign($input, $der, $private, $digest);

            return CallbackKey::derToJose((string) $der, $partLength);
        }, 'kms-key');

        for ($i = 0; $i < 20; $i++) { // DER R/S lengths vary; cover the padding cases
            JwtAssert::verified(Jws::sign(['alg' => $algorithm], ['n' => $i], $key), $key->publicJwk());
        }
        self::assertSame('kms-key', $key->keyId());
    }

    public function testRefusesPrivateJwkMembers(): void
    {
        $this->expectException(ConfigurationException::class);
        new CallbackKey('ES256', JWKFactory::createECKey('P-256')->all(), static fn (string $input): string => '');
    }
}
