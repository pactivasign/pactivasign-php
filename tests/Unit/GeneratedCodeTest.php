<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use PactivaSign\Client;
use PactivaSign\Tests\Support\SchemaSamples;
use PHPUnit\Framework\TestCase;

/**
 * Keeps the generated code honest: it must match what the generator produces from the vendored
 * spec, the spec must match its recorded tag, and every operation must have a method.
 */
final class GeneratedCodeTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    public function testTheVendoredSpecMatchesItsRecordedSource(): void
    {
        $source = json_decode((string) file_get_contents(self::ROOT . '/openapi/SOURCE.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($source);

        self::assertSame($source['sha256'], hash_file('sha256', self::ROOT . '/openapi/public.json'));
        self::assertMatchesRegularExpression('/^openapi-v\d+\.\d+\.\d+$/', $source['tag']);
    }

    public function testGeneratedFilesAreUpToDate(): void
    {
        $out = sys_get_temp_dir() . '/pactivasign-generated-' . bin2hex(random_bytes(4));
        exec(sprintf('php %s %s 2>&1', escapeshellarg(self::ROOT . '/tools/generate.php'), escapeshellarg($out)), $output, $exit);
        self::assertSame(0, $exit, implode("\n", $output));

        try {
            foreach (['Model', 'Resource'] as $dir) {
                foreach (glob("$out/$dir/*.php") ?: [] as $generated) {
                    $committed = self::ROOT . "/src/$dir/" . basename($generated);
                    self::assertFileExists($committed, 'run composer generate');
                    self::assertSame(file_get_contents($generated), file_get_contents($committed), basename($generated) . ' is stale; run composer generate');
                }
            }
        } finally {
            exec('rm -rf ' . escapeshellarg($out));
        }
    }

    public function testEveryOperationHasAMethod(): void
    {
        $client = new \ReflectionClass(Client::class);
        $count = 0;
        foreach ((new SchemaSamples())->spec()['paths'] as $item) {
            foreach ($item as $method => $operation) {
                if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }
                [$resource, $name] = explode('.', $operation['operationId'], 2);
                $type = $client->getProperty($resource)->getType();
                self::assertInstanceOf(\ReflectionNamedType::class, $type);
                self::assertTrue(method_exists($type->getName(), $name), $operation['operationId']);
                $count++;
            }
        }
        self::assertSame(41, $count);
    }
}
