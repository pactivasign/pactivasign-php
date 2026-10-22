<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use PactivaSign\Auth\Jws;
use PactivaSign\Exception\ConfigurationException;
use PactivaSign\Key\LocalKey;
use PactivaSign\Tests\Support\JwtAssert;
use PactivaSign\Tests\Support\RecordingLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalKeyTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function algorithms(): iterable
    {
        foreach (['ES256', 'ES384', 'ES512', 'PS256', 'RS256', 'EdDSA'] as $algorithm) {
            yield $algorithm => [$algorithm];
        }
    }

    #[DataProvider('algorithms')]
    public function testGeneratedKeysSignVerifiableJws(string $algorithm): void
    {
        $key = LocalKey::generate($algorithm, 'kid-1');

        $jws = Jws::sign(['alg' => $algorithm], ['hello' => 'world'], $key);

        $decoded = JwtAssert::verified($jws, $key->publicJwk());
        self::assertSame('world', $decoded['claims']['hello']);
        self::assertSame($algorithm, $key->algorithm());
        self::assertSame('kid-1', $key->keyId());
    }

    #[DataProvider('algorithms')]
    public function testExportedPrivateJwkLoadsBackAsTheSameKey(string $algorithm): void
    {
        $key = LocalKey::generate($algorithm, 'kid-2');

        $loaded = LocalKey::fromJwk($key->exportPrivateJwk());

        self::assertSame($key->thumbprint(), $loaded->thumbprint());
        self::assertSame($algorithm, $loaded->algorithm());
        self::assertSame('kid-2', $loaded->keyId());
    }

    public function testPublicJwkAndRegistrationJwkHoldNoPrivateMembers(): void
    {
        $key = LocalKey::generate('ES256', 'client-key-1');

        self::assertSame(['kty', 'crv', 'x', 'y'], array_keys($key->publicJwk()));
        self::assertSame(
            ['kty', 'crv', 'x', 'y', 'alg', 'use', 'kid'],
            array_keys($key->registrationJwk()),
        );
        self::assertSame('client-key-1', $key->registrationJwk()['kid']);
    }

    public function testRejectsAPublicKey(): void
    {
        $public = LocalKey::generate()->publicJwk();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('no private part');
        LocalKey::fromJwk($public);
    }

    public function testRejectsAKeyThatDoesNotMatchTheAlgorithm(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('does not match algorithm ES384');
        LocalKey::fromJwk(LocalKey::generate('ES256')->exportPrivateJwk(), 'ES384');
    }

    public function testRejectsUnsupportedAlgorithms(): void
    {
        $this->expectException(ConfigurationException::class);
        LocalKey::fromJwk(LocalKey::generate('ES256')->exportPrivateJwk(), 'HS256');
    }

    public function testRejectsShortRsaKeys(): void
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 1024]);
        self::assertNotFalse($resource);
        openssl_pkey_export($resource, $pem);
        $path = $this->tempFile((string) $pem);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('at least 2048 bits');
        LocalKey::fromFile($path, 'RS256');
    }

    public function testLoadsPemFilesAndInfersTheAlgorithm(): void
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'secp384r1']);
        self::assertNotFalse($resource);
        openssl_pkey_export($resource, $pem);

        $key = LocalKey::fromFile($this->tempFile((string) $pem, 0o600), keyId: 'pem-key');

        self::assertSame('ES384', $key->algorithm());
        self::assertSame('pem-key', $key->keyId());
    }

    public function testWarnsWhenTheKeyFileIsReadableByOthers(): void
    {
        $logger = new RecordingLogger();
        $path = $this->tempFile(LocalKey::generate()->exportPrivateJwk(), 0o644);

        LocalKey::fromFile($path, logger: $logger);

        self::assertCount(1, $logger->records);
        self::assertStringContainsString('0600', $logger->records[0]['message']);
    }

    public function testReadsKeysFromTheEnvironment(): void
    {
        $key = LocalKey::generate('EdDSA');
        $_SERVER['PACTIVASIGN_TEST_KEY'] = $key->exportPrivateJwk();

        try {
            self::assertSame($key->thumbprint(), LocalKey::fromEnvironment('PACTIVASIGN_TEST_KEY')->thumbprint());
        } finally {
            unset($_SERVER['PACTIVASIGN_TEST_KEY']);
        }
    }

    public function testNeverExposesPrivateMaterial(): void
    {
        $key = LocalKey::generate('ES256');
        $private = json_decode($key->exportPrivateJwk(), true);
        self::assertIsArray($private);

        $dump = print_r($key, true) . var_export($key->__debugInfo(), true);
        self::assertStringNotContainsString($private['d'], $dump);

        $this->expectException(\LogicException::class);
        serialize($key);
    }

    private function tempFile(string $contents, int $mode = 0o600): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pactivasign-key-');
        file_put_contents($path, $contents);
        chmod($path, $mode);
        register_shutdown_function(static fn () => @unlink($path));

        return $path;
    }
}
