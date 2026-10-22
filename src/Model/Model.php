<?php

declare(strict_types=1);

namespace PactivaSign\Model;

use PactivaSign\Exception\UnexpectedResponseException;

/**
 * Base class of the generated response models.
 *
 * Models are read-only. They keep the JSON object they were built from: toArray() returns it,
 * including fields this SDK version does not know yet and the raw value behind any `Unknown` enum case.
 *
 * @phpstan-consistent-constructor
 */
abstract class Model implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(private readonly array $data)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    /**
     * The JSON object as the API sent it.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    protected function has(string $key): bool
    {
        return array_key_exists($key, $this->data) && $this->data[$key] !== null;
    }

    protected function string(string $key): string
    {
        $value = $this->required($key);
        if (!is_string($value)) {
            throw $this->invalid($key, 'a string');
        }

        return $value;
    }

    protected function int(string $key): int
    {
        $value = $this->required($key);
        if (!is_int($value)) {
            throw $this->invalid($key, 'an integer');
        }

        return $value;
    }

    protected function number(string $key): int|float
    {
        $value = $this->required($key);
        if (!is_int($value) && !is_float($value)) {
            throw $this->invalid($key, 'a number');
        }

        return $value;
    }

    protected function bool(string $key): bool
    {
        $value = $this->required($key);
        if (!is_bool($value)) {
            throw $this->invalid($key, 'a boolean');
        }

        return $value;
    }

    protected function date(string $key): \DateTimeImmutable
    {
        $value = $this->string($key);
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw $this->invalid($key, 'an ISO 8601 date-time');
        }
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum an enum with an `Unknown` case
     * @return T
     */
    protected function enum(string $key, string $enum): \BackedEnum
    {
        return self::enumValue($this->string($key), $enum);
    }

    /**
     * @template T of Model
     * @param class-string<T> $model
     * @return T
     */
    protected function model(string $key, string $model): Model
    {
        return $model::fromArray($this->object($key));
    }

    /**
     * @template T of Model
     * @param class-string<T> $model
     * @return list<T>
     */
    protected function models(string $key, string $model): array
    {
        return array_map(
            fn (mixed $item) => is_array($item) ? $model::fromArray(self::assoc($item)) : throw $this->invalid($key, 'a list of objects'),
            $this->list($key),
        );
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum
     * @return list<T>
     */
    protected function enums(string $key, string $enum): array
    {
        return array_map(
            fn (mixed $item) => is_string($item) ? self::enumValue($item, $enum) : throw $this->invalid($key, 'a list of strings'),
            $this->list($key),
        );
    }

    /**
     * @return list<string>
     */
    protected function strings(string $key): array
    {
        return array_map(
            fn (mixed $item) => is_string($item) ? $item : throw $this->invalid($key, 'a list of strings'),
            $this->list($key),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function object(string $key): array
    {
        $value = $this->required($key);
        if (!is_array($value) || (array_is_list($value) && $value !== [])) {
            throw $this->invalid($key, 'an object');
        }

        return self::assoc($value);
    }

    /**
     * @return array<string, string>
     */
    protected function stringMap(string $key): array
    {
        $map = [];
        foreach ($this->object($key) as $name => $value) {
            $map[$name] = is_string($value) ? $value : throw $this->invalid($key, 'an object of strings');
        }

        return $map;
    }

    protected function stringOrBool(string $key): string|bool
    {
        $value = $this->required($key);
        if (!is_string($value) && !is_bool($value)) {
            throw $this->invalid($key, 'a string or a boolean');
        }

        return $value;
    }

    /**
     * @return list<mixed>
     */
    private function list(string $key): array
    {
        $value = $this->required($key);
        if (!is_array($value) || !array_is_list($value)) {
            throw $this->invalid($key, 'a list');
        }

        return $value;
    }

    private function required(string $key): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            throw new UnexpectedResponseException(sprintf('%s is missing "%s".', static::class, $key));
        }

        return $this->data[$key];
    }

    private function invalid(string $key, string $expected): UnexpectedResponseException
    {
        return new UnexpectedResponseException(sprintf('%s "%s" should be %s.', static::class, $key, $expected));
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum
     * @return T
     */
    private static function enumValue(string $value, string $enum): \BackedEnum
    {
        return $enum::tryFrom($value) ?? constant($enum . '::Unknown');
    }

    /**
     * @param array<mixed> $value
     * @return array<string, mixed>
     */
    private static function assoc(array $value): array
    {
        $assoc = [];
        foreach ($value as $key => $item) {
            $assoc[(string) $key] = $item;
        }

        return $assoc;
    }
}
