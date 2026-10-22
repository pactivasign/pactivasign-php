<?php

declare(strict_types=1);

namespace PactivaSign\Http;

use Psr\Http\Message\StreamInterface;

/**
 * A multipart/form-data body, built in memory so it can be resent unchanged on a retry.
 */
final class Multipart
{
    private readonly string $boundary;

    /** @var list<string> */
    private array $parts = [];

    public function __construct()
    {
        $this->boundary = 'pactivasign-' . bin2hex(random_bytes(12));
    }

    public function field(string $name, string $value): self
    {
        $this->parts[] = $this->headers($name) . "\r\n" . $value;

        return $this;
    }

    /**
     * @param string|resource|StreamInterface $contents file contents, an open stream or a PSR-7 stream
     */
    public function file(string $name, mixed $contents, string $filename, string $contentType = 'application/pdf'): self
    {
        if ($contents instanceof StreamInterface) {
            if ($contents->isSeekable()) {
                $contents->rewind();
            }
            $contents = $contents->getContents();
        } elseif (is_resource($contents)) {
            $contents = (string) stream_get_contents($contents);
        }
        if (!is_string($contents)) {
            throw new \InvalidArgumentException('File contents must be a string, a stream resource or a PSR-7 stream.');
        }
        $this->parts[] = $this->headers($name, $filename) . sprintf("Content-Type: %s\r\n\r\n", $contentType) . $contents;

        return $this;
    }

    public function contentType(): string
    {
        return 'multipart/form-data; boundary=' . $this->boundary;
    }

    public function body(): string
    {
        $body = '';
        foreach ($this->parts as $part) {
            $body .= '--' . $this->boundary . "\r\n" . $part . "\r\n";
        }

        return $body . '--' . $this->boundary . "--\r\n";
    }

    private function headers(string $name, ?string $filename = null): string
    {
        $disposition = sprintf('Content-Disposition: form-data; name="%s"', self::quote($name));
        if ($filename !== null) {
            $disposition .= sprintf('; filename="%s"', self::quote($filename));
        }

        return $disposition . "\r\n";
    }

    private static function quote(string $value): string
    {
        return str_replace(['"', "\r", "\n"], ['%22', '%0D', '%0A'], $value);
    }
}
