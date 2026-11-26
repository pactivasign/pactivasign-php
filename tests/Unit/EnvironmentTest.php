<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use PactivaSign\Environment;
use PactivaSign\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function testNamedEnvironments(): void
    {
        self::assertSame('https://pactiva-sandbox-api.pactivasign.com', Environment::sandbox()->baseUrl);
        self::assertSame('https://api.pactivasign.com', Environment::production()->baseUrl);
        self::assertSame('https://api.pactivasign.com/api/v1/oauth/token', Environment::production()->tokenEndpoint());
    }

    public function testRejectsPlainHttpOutsideLocalhost(): void
    {
        $this->expectException(ConfigurationException::class);
        new Environment('http://api.pactivasign.com');
    }
}
