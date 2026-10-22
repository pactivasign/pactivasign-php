<?php

declare(strict_types=1);

namespace PactivaSign;

use PactivaSign\Exception\ConfigurationException;

/**
 * Which PactivaSign deployment to talk to. It is part of every cache key, so tokens for one
 * environment are never used against another.
 */
final class Environment
{
    public const STAGING_URL = 'https://pactiva-api.duckdns.org';

    /** Origin of the API, without a trailing slash, e.g. https://api.example.com */
    public readonly string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        $parts = parse_url($baseUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($parts === false || $host === '' || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user'])) {
            throw new ConfigurationException(sprintf('"%s" is not a valid PactivaSign base URL.', $baseUrl));
        }
        $local = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
        if ($scheme !== 'https' && !($scheme === 'http' && $local)) {
            throw new ConfigurationException('The PactivaSign base URL must use https (http is allowed for localhost only).');
        }
        $this->baseUrl = $baseUrl;
    }

    public static function staging(): self
    {
        return new self(self::STAGING_URL);
    }

    public function tokenEndpoint(): string
    {
        return $this->baseUrl . '/api/v1/oauth/token';
    }

    public function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
}
