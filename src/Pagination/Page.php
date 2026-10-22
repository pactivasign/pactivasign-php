<?php

declare(strict_types=1);

namespace PactivaSign\Pagination;

/**
 * One page of a list.
 *
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
final class Page implements \IteratorAggregate, \Countable
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor,
    ) {
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
