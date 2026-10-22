<?php

declare(strict_types=1);

namespace PactivaSign\Http;

use Psr\Http\Message\StreamInterface;

/**
 * A downloaded file, e.g. a signed PDF. The body is a stream, so large files need not be held in memory.
 */
final class Download
{
    public function __construct(private readonly Response $response)
    {
    }

    /** True when the server answered 304 to an If-None-Match: your copy is current and there is no body. */
    public function isNotModified(): bool
    {
        return $this->response->statusCode() === 304;
    }

    public function stream(): StreamInterface
    {
        return $this->response->body();
    }

    public function contents(): string
    {
        $stream = $this->stream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return $stream->getContents();
    }

    /** Writes the file to `$path` in chunks and returns the number of bytes written. */
    public function saveTo(string $path): int
    {
        $stream = $this->stream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $handle = @fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Cannot open "%s" for writing.', $path));
        }
        $bytes = 0;
        try {
            while (!$stream->eof()) {
                $bytes += (int) fwrite($handle, $stream->read(65536));
            }
        } finally {
            fclose($handle);
        }

        return $bytes;
    }

    public function contentType(): ?string
    {
        return $this->response->header('Content-Type');
    }

    /** Pass this as `ifNoneMatch` next time to skip the download when nothing changed. */
    public function etag(): ?string
    {
        return $this->response->header('ETag');
    }

    public function filename(): ?string
    {
        $disposition = (string) $this->response->header('Content-Disposition');

        return preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $match) === 1 ? rawurldecode($match[1]) : null;
    }

    public function requestId(): ?string
    {
        return $this->response->requestId();
    }
}
