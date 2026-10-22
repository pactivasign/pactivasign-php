<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Support;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Assert;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that answers from a script and records every request, with its body read once.
 */
final class ScriptedHttpClient implements ClientInterface
{
    /** @var list<\Closure(RequestInterface): (ResponseInterface|\Throwable)> */
    private array $script = [];

    /** @var list<array{request: RequestInterface, body: string}> */
    public array $sent = [];

    /**
     * @param \Closure(RequestInterface): (ResponseInterface|\Throwable)|ResponseInterface|\Throwable $answer
     */
    public function then(\Closure|ResponseInterface|\Throwable $answer): self
    {
        $this->script[] = $answer instanceof \Closure ? $answer : static fn () => $answer;

        return $this;
    }

    /**
     * @param array<string, string|list<string>> $headers
     * @param array<mixed>|string|null $body
     */
    public static function response(int $status, array|string|null $body = null, array $headers = []): ResponseInterface
    {
        if (is_array($body)) {
            $headers += ['Content-Type' => 'application/json'];
            $body = json_encode($body, JSON_THROW_ON_ERROR);
        }

        return new Response($status, $headers, $body);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function token(string $value = 'access-token-1', int $expiresIn = 900, array $headers = []): ResponseInterface
    {
        return self::response(200, [
            'access_token' => $value,
            'token_type' => 'DPoP',
            'expires_in' => $expiresIn,
            'scope' => 'documents:read',
        ], $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function apiError(int $status, string $code, string $requestId = 'req-1', array $headers = []): ResponseInterface
    {
        return self::response($status, [
            'success' => false,
            'error' => ['code' => $code, 'message' => "API said $code", 'requestId' => $requestId],
        ], $headers + ['X-Request-Id' => $requestId]);
    }

    public static function networkError(RequestInterface $request): NetworkExceptionInterface
    {
        return new class ('Connection reset', $request) extends \RuntimeException implements NetworkExceptionInterface {
            public function __construct(string $message, private readonly RequestInterface $request)
            {
                parent::__construct($message);
            }

            public function getRequest(): RequestInterface
            {
                return $this->request;
            }
        };
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sent[] = ['request' => $request, 'body' => (string) $request->getBody()];
        $next = array_shift($this->script);
        if ($next === null) {
            Assert::fail(sprintf('Unexpected request %s %s', $request->getMethod(), $request->getUri()));
        }
        $answer = $next($request);
        if ($answer instanceof \Throwable) {
            throw $answer;
        }

        return $answer;
    }

    /**
     * The urlencoded form a request sent, as strings.
     *
     * @return array<string, string>
     */
    public static function form(string $body): array
    {
        parse_str($body, $parsed);
        $form = [];
        foreach ($parsed as $name => $value) {
            $form[(string) $name] = is_string($value) ? $value : '';
        }

        return $form;
    }

    public function assertDone(): void
    {
        Assert::assertSame([], $this->script, 'Every scripted answer was used');
    }

    /**
     * @return list<array{request: RequestInterface, body: string}>
     */
    public function sentTo(string $pathSuffix): array
    {
        return array_values(array_filter(
            $this->sent,
            static fn (array $sent) => str_ends_with($sent['request']->getUri()->getPath(), $pathSuffix),
        ));
    }
}
