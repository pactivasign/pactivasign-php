<?php

declare(strict_types=1);

namespace PactivaSign\Auth;

use PactivaSign\Environment;
use PactivaSign\Exception\AuthenticationException;
use PactivaSign\Exception\ErrorFactory;
use PactivaSign\Exception\TransportException;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Gets DPoP-bound access tokens with the client credentials grant and keeps them in the cache.
 *
 * There is no refresh token: a token is replaced by authenticating again shortly before it expires,
 * or when the API rejects it. The cache key covers the environment, client ID, scopes and DPoP key
 * thumbprint, and the cache never holds private keys.
 *
 * @internal
 */
final class TokenProvider
{
    /** Renew this long before expiry, to absorb clock drift and slow requests. */
    public const RENEW_BEFORE_SECONDS = 60;

    private const LOCK_TTL_SECONDS = 10;
    private const LOCK_WAIT_MICROSECONDS = 5_000_000;
    private const LOCK_POLL_MICROSECONDS = 100_000;

    private readonly string $cacheKey;
    private ?AccessToken $current = null;
    /** @var \Closure(int): void */
    private readonly \Closure $sleep;

    /**
     * @param list<string> $scopes
     * @param (\Closure(int): void)|null $sleep microseconds; replaced in tests
     */
    public function __construct(
        private readonly Environment $environment,
        private readonly string $clientId,
        private readonly array $scopes,
        private readonly ClientAssertionFactory $assertions,
        private readonly DpopProofFactory $proofs,
        private readonly NonceStore $nonces,
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly CacheInterface $cache,
        private readonly TokenLock $lock,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly string $userAgent,
        ?\Closure $sleep = null,
    ) {
        $sorted = $scopes;
        sort($sorted);
        $this->cacheKey = 'pactivasign.token.' . hash('sha256', implode("\n", [
            $environment->baseUrl,
            $clientId,
            implode(' ', $sorted),
            $proofs->thumbprint(),
        ]));
        $this->sleep = $sleep ?? static fn (int $microseconds) => usleep($microseconds);
    }

    /**
     * A token that is valid for at least RENEW_BEFORE_SECONDS more.
     *
     * Pass the token the API just rejected as `$rejected` to replace it. If another worker has
     * already replaced it, the newer cached token is returned instead of fetching yet another.
     */
    public function token(?string $rejected = null): AccessToken
    {
        $cached = $this->cached();
        if ($cached !== null && $cached->value !== $rejected) {
            return $this->current = $cached;
        }
        if ($rejected !== null) {
            $this->forget();
        }

        $lockKey = $this->cacheKey . '.lock';
        if (!$this->lock->acquire($lockKey, self::LOCK_TTL_SECONDS)) {
            $waited = $this->waitForOtherWorker($rejected);
            if ($waited !== null) {
                return $this->current = $waited;
            }
            // The other worker is slow or failed; fetch our own rather than fail.
            return $this->current = $this->fetchAndStore();
        }

        try {
            // Another worker may have finished while we were waiting for the lock.
            $cached = $this->cached();
            if ($cached !== null && $cached->value !== $rejected) {
                return $this->current = $cached;
            }

            return $this->current = $this->fetchAndStore();
        } finally {
            $this->lock->release($lockKey);
        }
    }

    /** Drops the cached token, e.g. after changing keys or scopes. */
    public function forget(): void
    {
        $this->current = null;
        $this->cache->delete($this->cacheKey);
    }

    private function cached(): ?AccessToken
    {
        $now = $this->clock->now()->getTimestamp();
        if ($this->current !== null && !$this->current->expiresWithin(self::RENEW_BEFORE_SECONDS, $now)) {
            return $this->current;
        }
        $data = $this->cache->get($this->cacheKey);
        if (!is_array($data) || !is_string($data['value'] ?? null) || !is_int($data['expiresAt'] ?? null)) {
            return null;
        }
        $token = new AccessToken($data['value'], $data['expiresAt'], is_string($data['scope'] ?? null) ? $data['scope'] : '');

        return $token->expiresWithin(self::RENEW_BEFORE_SECONDS, $now) ? null : $token;
    }

    private function waitForOtherWorker(?string $rejected): ?AccessToken
    {
        for ($waited = 0; $waited < self::LOCK_WAIT_MICROSECONDS; $waited += self::LOCK_POLL_MICROSECONDS) {
            ($this->sleep)(self::LOCK_POLL_MICROSECONDS);
            $cached = $this->cached();
            if ($cached !== null && $cached->value !== $rejected) {
                return $cached;
            }
        }
        $this->logger->info('PactivaSign token lock wait timed out; fetching a token directly.');

        return null;
    }

    private function fetchAndStore(): AccessToken
    {
        $token = $this->fetch();
        $ttl = $token->expiresAt - $this->clock->now()->getTimestamp() - self::RENEW_BEFORE_SECONDS;
        if ($ttl > 0) {
            $this->cache->set($this->cacheKey, [
                'value' => $token->value,
                'expiresAt' => $token->expiresAt,
                'scope' => $token->scope,
            ], $ttl);
        }

        return $token;
    }

    private function fetch(): AccessToken
    {
        $endpoint = $this->environment->tokenEndpoint();

        for ($attempt = 1; ; $attempt++) {
            $form = http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'scope' => implode(' ', $this->scopes),
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $this->assertions->create(),
            ], '', '&', PHP_QUERY_RFC3986);

            $request = $this->requestFactory->createRequest('POST', $endpoint)
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withHeader('Accept', 'application/json')
                ->withHeader('User-Agent', $this->userAgent)
                ->withHeader('DPoP', $this->proofs->create('POST', $endpoint, null, $this->nonces->get(NonceStore::TOKEN_ENDPOINT)))
                ->withBody($this->streamFactory->createStream($form));

            try {
                $response = $this->http->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                throw new TransportException('Could not reach the PactivaSign token endpoint: ' . $e->getMessage(), 0, $e);
            }

            $nonce = $response->getHeaderLine('DPoP-Nonce');
            if ($nonce !== '') {
                $this->nonces->remember(NonceStore::TOKEN_ENDPOINT, $nonce);
            }

            if ($response->getStatusCode() === 200) {
                return $this->parse($response);
            }

            $error = ErrorFactory::fromResponse($response);
            if ($error->errorCode === 'use_dpop_nonce' && $nonce !== '' && $attempt === 1) {
                $this->logger->debug('PactivaSign token endpoint asked for a DPoP nonce; retrying with it.');
                continue;
            }

            $this->logger->warning('PactivaSign token request failed.', [
                'status' => $error->statusCode,
                'error' => $error->errorCode,
                'request_id' => $error->requestId,
            ]);

            throw $error;
        }
    }

    private function parse(ResponseInterface $response): AccessToken
    {
        $body = json_decode((string) $response->getBody(), true);
        $value = is_array($body) ? ($body['access_token'] ?? null) : null;
        $type = is_array($body) ? ($body['token_type'] ?? null) : null;
        $expiresIn = is_array($body) ? ($body['expires_in'] ?? null) : null;

        if (!is_string($value) || $value === '' || !is_string($type) || strcasecmp($type, 'DPoP') !== 0 || !is_int($expiresIn)) {
            throw new AuthenticationException(
                'The token endpoint returned an unexpected response.',
                $response->getStatusCode(),
                'invalid_token_response',
                $response->getHeaderLine('X-Request-Id') ?: null,
                [],
                $response,
            );
        }

        return new AccessToken(
            $value,
            $this->clock->now()->getTimestamp() + $expiresIn,
            is_string($body['scope'] ?? null) ? $body['scope'] : implode(' ', $this->scopes),
        );
    }
}
