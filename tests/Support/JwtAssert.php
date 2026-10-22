<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Support;

use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm;
use PHPUnit\Framework\Assert;

/**
 * Decodes compact JWS values and checks their signatures independently of the SDK's signing code.
 */
final class JwtAssert
{
    /**
     * @param array<string, string>|null $publicJwk verify against this key; defaults to the `jwk` header
     * @return array{header: array<string, mixed>, claims: array<string, mixed>}
     */
    public static function verified(string $jws, ?array $publicJwk = null): array
    {
        $parts = explode('.', $jws);
        Assert::assertCount(3, $parts, 'compact JWS has three parts');
        $header = self::decode($parts[0]);
        $claims = self::decode($parts[1]);
        $key = new JWK($publicJwk ?? $header['jwk']);
        $class = 'Jose\\Component\\Signature\\Algorithm\\' . $header['alg'];
        /** @var Algorithm\SignatureAlgorithm $algorithm */
        $algorithm = new $class();
        Assert::assertTrue(
            $algorithm->verify($key, $parts[0] . '.' . $parts[1], self::base64UrlDecode($parts[2])),
            'JWS signature verifies',
        );

        return ['header' => $header, 'claims' => $claims];
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(string $part): array
    {
        $decoded = json_decode(self::base64UrlDecode($part), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertIsArray($decoded);

        return $decoded;
    }

    private static function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
