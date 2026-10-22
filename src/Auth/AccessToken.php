<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

/**
 * A DPoP-bound access token. Useless without the DPoP private key, but still kept out of logs.
 */
final class AccessToken
{
    public function __construct(
        public readonly string $value,
        public readonly int $expiresAt,
        public readonly string $scope,
    ) {
    }

    public function expiresWithin(int $seconds, int $now): bool
    {
        return $this->expiresAt - $seconds <= $now;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['expiresAt' => $this->expiresAt, 'scope' => $this->scope];
    }
}
