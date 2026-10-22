<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Passes requests to a real client and records their paths.
 */
final class RecordingHttpClient implements ClientInterface
{
    /** @var list<string> */
    private array $paths = [];

    public function __construct(private readonly ClientInterface $inner)
    {
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    public function reset(): void
    {
        $this->paths = [];
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->paths[] = $request->getUri()->getPath();

        return $this->inner->sendRequest($request);
    }
}
