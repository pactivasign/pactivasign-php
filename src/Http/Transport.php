<?php

declare(strict_types=1);

namespace PactivaSign\Http;

use PactivaSign\Auth\DpopProofFactory;
use PactivaSign\Auth\NonceStore;
use PactivaSign\Auth\TokenProvider;
use PactivaSign\Environment;
use PactivaSign\Exception\ApiException;
use PactivaSign\Exception\ErrorFactory;
use PactivaSign\Exception\RateLimitException;
use PactivaSign\Exception\ServerException;
use PactivaSign\Exception\TransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends authenticated API requests.
 *
 * For every attempt it takes a valid access token, signs a fresh DPoP proof for that exact request,
 * and handles the server's answers:
 *
 * - `use_dpop_nonce`: retried once with a new proof carrying the nonce (same body, same Idempotency-Key);
 * - `invalid_token`: a new token is fetched once and the request replayed (same Idempotency-Key);
 * - network errors, 429, 502, 503, 504 and IDEMPOTENCY_IN_PROGRESS: retried with backoff, for safe
 *   requests only (see RetryPolicy).
 *
 * @internal
 */
final class Transport
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /** @var \Closure(int): void */
    private readonly \Closure $sleep;

    /**
     * @param (\Closure(int): void)|null $sleep microseconds; replaced in tests
     */
    public function __construct(
        private readonly Environment $environment,
        private readonly TokenProvider $tokens,
        private readonly DpopProofFactory $proofs,
        private readonly NonceStore $nonces,
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly RetryPolicy $retryPolicy,
        private readonly LoggerInterface $logger,
        private readonly string $userAgent,
        ?\Closure $sleep = null,
    ) {
        $this->sleep = $sleep ?? static fn (int $microseconds) => usleep($microseconds);
    }

    /**
     * @param array{
     *     query?: array<string, mixed>,
     *     json?: mixed,
     *     multipart?: Multipart,
     *     body?: string|StreamInterface,
     *     contentType?: string,
     *     headers?: array<string, string>,
     *     idempotencyKey?: string,
     * } $options
     */
    public function send(string $method, string $path, array $options = []): Response
    {
        $method = strtoupper($method);
        $url = $this->environment->url($path);
        if (!empty($options['query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($options['query'], '', '&', PHP_QUERY_RFC3986);
        }

        [$body, $contentType] = $this->body($options);
        $headers = ($options['headers'] ?? []) + ['Accept' => 'application/json'];
        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }
        $idempotencyKey = $options['idempotencyKey'] ?? null;
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $replayable = !$body instanceof StreamInterface || $body->isSeekable();
        $retryable = $replayable && (in_array($method, self::SAFE_METHODS, true) || $idempotencyKey !== null);

        $attempt = 1;
        $nonceRetried = false;
        $tokenRenewed = false;
        $rejectedToken = null;

        while (true) {
            try {
                $token = $this->tokens->token($rejectedToken);
            } catch (TransportException | ServerException | RateLimitException $e) {
                // Nothing reached the API yet, so waiting and trying again is safe for any method.
                $retryAfter = $e instanceof ApiException && $e->response !== null ? ErrorFactory::retryAfter($e->response) : null;
                $delay = $this->retryPolicy->delayMs($attempt, $retryAfter);
                if ($delay === null) {
                    throw $e;
                }
                $this->backoff($method, $path, $attempt++, $delay, 'token: ' . $e->getMessage());
                continue;
            }
            $rejectedToken = null;

            try {
                $response = $this->attempt($method, $url, $headers, $body, $token->value);
            } catch (TransportException $e) {
                // The server may have acted on the request, so only safe requests are resent.
                $delay = $retryable ? $this->retryPolicy->delayMs($attempt, null) : null;
                if ($delay === null) {
                    throw $e;
                }
                $this->backoff($method, $path, $attempt++, $delay, $e->getMessage());
                continue;
            }

            $status = $response->getStatusCode();
            if ($status < 400) {
                return new Response($response);
            }

            if ($status === 401) {
                $challenge = ErrorFactory::oauthCodeFromChallenge($response) ?? $this->oauthBodyCode($response);
                if ($challenge === 'use_dpop_nonce' && !$nonceRetried && $replayable && $response->getHeaderLine('DPoP-Nonce') !== '') {
                    $nonceRetried = true;
                    continue;
                }
                if ($challenge === 'invalid_token' && !$tokenRenewed && $replayable) {
                    $tokenRenewed = true;
                    $rejectedToken = $token->value;
                    $this->logger->info('PactivaSign rejected the access token; authenticating again.', [
                        'request_id' => $response->getHeaderLine('X-Request-Id') ?: null,
                    ]);
                    continue;
                }
            }

            $error = ErrorFactory::fromResponse($response);
            $delay = $retryable && $this->isRetryable($error)
                ? $this->retryPolicy->delayMs($attempt, ErrorFactory::retryAfter($response) ?? ($error->errorCode === 'IDEMPOTENCY_IN_PROGRESS' ? 1 : null))
                : null;
            if ($delay === null) {
                throw $error;
            }
            $this->backoff($method, $path, $attempt++, $delay, sprintf('HTTP %d %s', $status, $error->errorCode), $error->requestId);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function attempt(string $method, string $url, array $headers, string|StreamInterface|null $body, string $accessToken): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Authorization', 'DPoP ' . $accessToken)
            ->withHeader('DPoP', $this->proofs->create($method, $url, $accessToken, $this->nonces->get(NonceStore::API)))
            ->withHeader('User-Agent', $this->userAgent);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if ($body instanceof StreamInterface) {
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $request = $request->withBody($body);
        } elseif ($body !== null) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(sprintf('%s %s failed: %s', $method, $url, $e->getMessage()), 0, $e);
        }

        $nonce = $response->getHeaderLine('DPoP-Nonce');
        if ($nonce !== '') {
            $this->nonces->remember(NonceStore::API, $nonce);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $options
     * @return array{string|StreamInterface|null, ?string}
     */
    private function body(array $options): array
    {
        if (array_key_exists('json', $options)) {
            return [json_encode($options['json'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'application/json'];
        }
        if (isset($options['multipart']) && $options['multipart'] instanceof Multipart) {
            return [$options['multipart']->body(), $options['multipart']->contentType()];
        }
        if (isset($options['body'])) {
            $contentType = $options['contentType'] ?? 'application/octet-stream';

            return [$options['body'], is_string($contentType) ? $contentType : null];
        }

        return [null, null];
    }

    private function isRetryable(ApiException $error): bool
    {
        return in_array($error->statusCode, RetryPolicy::RETRY_STATUSES, true)
            || $error->errorCode === 'IDEMPOTENCY_IN_PROGRESS';
    }

    private function oauthBodyCode(ResponseInterface $response): ?string
    {
        $body = $response->getBody();
        $decoded = json_decode((string) $body, true);
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return is_array($decoded) && is_string($decoded['error'] ?? null) ? $decoded['error'] : null;
    }

    private function backoff(string $method, string $path, int $attempt, int $delayMs, string $reason, ?string $requestId = null): void
    {
        $this->logger->warning('PactivaSign request failed; retrying.', [
            'method' => $method,
            'path' => $path,
            'attempt' => $attempt,
            'delay_ms' => $delayMs,
            'reason' => $reason,
            'request_id' => $requestId,
        ]);
        ($this->sleep)($delayMs * 1000);
    }
}
