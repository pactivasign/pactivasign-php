<?php

declare(strict_types=1);

namespace PactivaSign\Resource;

use PactivaSign\Exception\UnexpectedResponseException;
use PactivaSign\Http\Response;
use PactivaSign\Http\Transport;
use PactivaSign\Model\Model;
use PactivaSign\Pagination\Page;

/**
 * Base class of the generated resource classes.
 *
 * @internal
 */
abstract class ApiResource
{
    final public function __construct(protected readonly Transport $transport)
    {
    }

    /** Builds a path, URL-encoding each parameter. */
    protected static function path(string $template, string ...$parameters): string
    {
        foreach ($parameters as $parameter) {
            if ($parameter === '') {
                throw new \InvalidArgumentException('Path parameters must not be empty.');
            }
        }

        return sprintf($template, ...array_map('rawurlencode', $parameters));
    }

    /**
     * Turns DateTimeInterface values into ISO 8601 strings and enums into their values, recursively.
     */
    protected static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::RFC3339_EXTENDED),
            $value instanceof \BackedEnum => $value->value,
            is_array($value) => array_map(self::normalize(...), $value),
            default => $value,
        };
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    protected static function query(array $query): array
    {
        $normalized = self::normalize(array_filter($query, static fn ($value) => $value !== null));

        return array_map(static fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value, is_array($normalized) ? $normalized : []);
    }

    /**
     * @template T of Model
     * @param class-string<T> $model
     * @return T
     */
    protected static function one(Response $response, string $model): Model
    {
        $data = $response->json()['data'] ?? null;
        if (!is_array($data)) {
            throw new UnexpectedResponseException('The response has no "data" object.');
        }

        return $model::fromArray(self::assoc($data));
    }

    /**
     * @template T of Model
     * @param class-string<T> $model
     * @return list<T>
     */
    protected static function many(Response $response, string $model): array
    {
        $data = $response->json()['data'] ?? null;
        if (!is_array($data) || !array_is_list($data)) {
            throw new UnexpectedResponseException('The response has no "data" list.');
        }

        return array_map(
            static fn (mixed $item) => is_array($item) ? $model::fromArray(self::assoc($item)) : throw new UnexpectedResponseException('A list item is not an object.'),
            $data,
        );
    }

    /**
     * @template T of Model
     * @param class-string<T> $model
     * @return Page<T>
     */
    protected static function page(Response $response, string $model): Page
    {
        $next = $response->json()['nextCursor'] ?? null;

        return new Page(self::many($response, $model), is_string($next) && $next !== '' ? $next : null);
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
