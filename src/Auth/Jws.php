<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

use PactivaSign\Key\KeyProvider;

/**
 * @internal
 */
final class Jws
{
    /**
     * @param array<string, mixed> $header
     * @param array<string, mixed> $claims
     */
    public static function sign(array $header, array $claims, KeyProvider $key): string
    {
        $input = self::base64Url(self::json($header)) . '.' . self::base64Url(self::json($claims));

        return $input . '.' . self::base64Url($key->sign($input));
    }

    public static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** Random RFC 4122 version 4 UUID, used for `jti` values and idempotency keys. */
    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function json(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
