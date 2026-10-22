<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Support;

/**
 * Builds sample JSON objects that satisfy a schema in openapi/public.json.
 */
final class SchemaSamples
{
    /** @var array<string, mixed> */
    private readonly array $spec;

    public function __construct()
    {
        $spec = json_decode((string) file_get_contents(__DIR__ . '/../../openapi/public.json'), true, 512, JSON_THROW_ON_ERROR);
        \PHPUnit\Framework\Assert::assertIsArray($spec);
        $this->spec = $spec;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function schemas(): array
    {
        /** @var array<string, array<string, mixed>> */
        return $this->spec['components']['schemas'];
    }

    /**
     * @return array<string, mixed>
     */
    public function spec(): array
    {
        return $this->spec;
    }

    /**
     * @param bool $nulls fill nullable fields with null instead of a value
     * @return array<string, mixed>
     */
    public function object(string $schema, bool $nulls = false): array
    {
        $value = $this->value(['$ref' => "#/components/schemas/$schema"], $nulls, $schema);
        \PHPUnit\Framework\Assert::assertIsArray($value);

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function value(array $schema, bool $nulls, string $path): mixed
    {
        if ($nulls && !empty($schema['nullable'])) {
            return null;
        }
        if (isset($schema['allOf'])) {
            return $this->value($schema['allOf'][0], $nulls, $path);
        }
        if (isset($schema['$ref'])) {
            $name = substr($schema['$ref'], strrpos($schema['$ref'], '/') + 1);

            return $this->value($this->schemas()[$name], $nulls, $path);
        }
        if (isset($schema['oneOf'])) {
            return 'one-of';
        }
        if (isset($schema['enum'])) {
            return $schema['enum'][0];
        }

        return match ($schema['type'] ?? null) {
            'string' => match ($schema['format'] ?? null) {
                'date-time' => '2026-10-22T10:30:00.000Z',
                'uuid' => '00000000-0000-4000-8000-' . str_pad((string) (crc32($path) % 1000000000000), 12, '0', STR_PAD_LEFT),
                'email' => 'jo@example.com',
                'uri' => 'https://example.com/hook',
                default => "sample:$path",
            },
            'integer' => 3,
            'number' => 2.5,
            'boolean' => true,
            'array' => [$this->value($schema['items'], $nulls, "{$path}[]")],
            'object' => isset($schema['properties'])
                ? array_combine(
                    array_keys($schema['properties']),
                    array_map(fn ($name, $property) => $this->value($property, $nulls, "$path.$name"), array_keys($schema['properties']), $schema['properties']),
                )
                : ['key' => 'value'],
            default => throw new \LogicException("No sample for $path"),
        };
    }
}
