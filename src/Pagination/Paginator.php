<?php

declare(strict_types=1);

namespace PactivaSign\Pagination;

/**
 * A list that fetches its pages lazily, following `nextCursor`.
 *
 *     foreach ($pactiva->envelopes->list(['status' => 'SENT']) as $envelope) { ... }   // every item, page by page
 *     $page = $pactiva->envelopes->list(['limit' => 10])->firstPage();                    // one page only
 *
 * Nothing is requested until you iterate or call firstPage().
 *
 * @template T
 * @implements \IteratorAggregate<int, T>
 */
final class Paginator implements \IteratorAggregate
{
    /** @var Page<T>|null */
    private ?Page $first = null;

    /**
     * @param \Closure(?string): Page<T> $fetch loads the page at a cursor (null = first page)
     */
    public function __construct(private readonly \Closure $fetch)
    {
    }

    /**
     * @return Page<T>
     */
    public function firstPage(): Page
    {
        return $this->first ??= ($this->fetch)(null);
    }

    /**
     * @return \Generator<int, Page<T>>
     */
    public function pages(): \Generator
    {
        $page = $this->firstPage();
        yield $page;
        while ($page->nextCursor !== null) {
            $page = ($this->fetch)($page->nextCursor);
            yield $page;
        }
    }

    /**
     * @return \Generator<int, T>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->pages() as $page) {
            yield from $page->items;
        }
    }

    /**
     * Every item of every page. Mind the size of the list before calling this.
     *
     * @return list<T>
     */
    public function all(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }
}
