<?php

declare(strict_types=1);

namespace PactivaSign\Tests\Unit;

use Nyholm\Psr7\Factory\Psr17Factory;
use PactivaSign\Auth\TokenLock;
use PactivaSign\Cache\ArrayCache;
use PactivaSign\Client;
use PactivaSign\Exception\AuthenticationException;
use PactivaSign\Exception\ConfigurationException;
use PactivaSign\Exception\IdempotencyConflictException;
use PactivaSign\Exception\NotFoundException;
use PactivaSign\Exception\PermissionException;
use PactivaSign\Exception\RateLimitException;
use PactivaSign\Exception\ServerException;
use PactivaSign\Exception\TransportException;
use PactivaSign\Http\RetryPolicy;
use PactivaSign\Key\Jwk;
use PactivaSign\Key\LocalKey;
use PactivaSign\Tests\Support\FrozenClock;
use PactivaSign\Tests\Support\JwtAssert;
use PactivaSign\Tests\Support\RecordingLogger;
use PactivaSign\Tests\Support\ScriptedHttpClient as Http;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\SimpleCache\CacheInterface;

final class ClientTest extends TestCase
{
    private const BASE = 'https://api.pactiva.test';
    private const CLIENT_ID = 'pcl_test123';

    private Http $http;
    private FrozenClock $clock;
    private RecordingLogger $logger;
    private LocalKey $clientKey;
    private LocalKey $dpopKey;
    /** @var list<int> microseconds */
    private array $sleeps = [];

    protected function setUp(): void
    {
        $this->http = new Http();
        $this->clock = new FrozenClock();
        $this->logger = new RecordingLogger();
        $this->clientKey = LocalKey::generate('ES256', 'client-key-1');
        $this->dpopKey = LocalKey::generate('ES256');
    }

    public function testTokenRequestUsesPrivateKeyJwtAndADpopProof(): void
    {
        $this->http->then(Http::token())->then(Http::response(200, ['success' => true, 'data' => ['id' => 'me']]));

        $data = $this->client()->request('GET', '/api/v1/me')->data();

        self::assertSame(['id' => 'me'], $data);
        [$tokenCall] = $this->http->sentTo('/oauth/token');
        $request = $tokenCall['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame(self::BASE . '/api/v1/oauth/token', (string) $request->getUri());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));

        $form = Http::form($tokenCall['body']);
        self::assertSame('client_credentials', $form['grant_type']);
        self::assertSame(self::CLIENT_ID, $form['client_id']);
        self::assertSame('documents:read signing:create', $form['scope']);
        self::assertSame('urn:ietf:params:oauth:client-assertion-type:jwt-bearer', $form['client_assertion_type']);

        $assertion = JwtAssert::verified($form['client_assertion'], $this->clientKey->publicJwk());
        self::assertSame(['alg' => 'ES256', 'typ' => 'JWT', 'kid' => 'client-key-1'], $assertion['header']);
        self::assertSame(self::CLIENT_ID, $assertion['claims']['iss']);
        self::assertSame(self::CLIENT_ID, $assertion['claims']['sub']);
        self::assertSame(self::BASE, $assertion['claims']['aud']);
        self::assertSame(60, $assertion['claims']['exp'] - $assertion['claims']['iat']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $assertion['claims']['jti']);

        $proof = JwtAssert::verified($request->getHeaderLine('DPoP'));
        self::assertSame('dpop+jwt', $proof['header']['typ']);
        self::assertSame($this->dpopKey->publicJwk(), $proof['header']['jwk']);
        self::assertSame('POST', $proof['claims']['htm']);
        self::assertSame(self::BASE . '/api/v1/oauth/token', $proof['claims']['htu']);
        self::assertSame($this->clock->now()->getTimestamp(), $proof['claims']['iat']);
        self::assertArrayNotHasKey('ath', $proof['claims']);
        self::assertArrayNotHasKey('nonce', $proof['claims']);
    }

    public function testApiCallsCarryTheTokenAndAProofBoundToIt(): void
    {
        $this->http->then(Http::token('tok-A'))->then(Http::response(200, ['success' => true, 'data' => []]));

        $this->client()->request('GET', '/api/v1/envelopes', ['query' => ['status' => 'SENT', 'limit' => 10]]);

        [$call] = $this->http->sentTo('/api/v1/envelopes');
        $request = $call['request'];
        self::assertSame('DPoP tok-A', $request->getHeaderLine('Authorization'));
        self::assertSame('status=SENT&limit=10', $request->getUri()->getQuery());
        self::assertStringStartsWith('pactivasign-php/', $request->getHeaderLine('User-Agent'));

        $proof = JwtAssert::verified($request->getHeaderLine('DPoP'));
        self::assertSame('GET', $proof['claims']['htm']);
        self::assertSame(self::BASE . '/api/v1/envelopes', $proof['claims']['htu'], 'htu has no query string');
        self::assertSame(rtrim(strtr(base64_encode(hash('sha256', 'tok-A', true)), '+/', '-_'), '='), $proof['claims']['ath']);
        self::assertSame(Jwk::thumbprint($proof['header']['jwk']), $this->dpopKey->thumbprint());
    }

    public function testTokenEndpointNonceChallengeIsAnsweredOnceWithAFreshProofAndAssertion(): void
    {
        $this->http
            ->then(Http::response(400, ['error' => 'use_dpop_nonce', 'error_description' => 'nonce'], ['DPoP-Nonce' => 'nonce-1']))
            ->then(Http::token())
            ->then(Http::response(200, ['success' => true, 'data' => []]));

        $this->client()->request('GET', '/api/v1/me');

        $calls = $this->http->sentTo('/oauth/token');
        self::assertCount(2, $calls);
        $first = JwtAssert::verified($calls[0]['request']->getHeaderLine('DPoP'))['claims'];
        $second = JwtAssert::verified($calls[1]['request']->getHeaderLine('DPoP'))['claims'];
        self::assertArrayNotHasKey('nonce', $first);
        self::assertSame('nonce-1', $second['nonce']);
        self::assertNotSame($first['jti'], $second['jti']);
        self::assertNotSame(Http::form($calls[0]['body'])['client_assertion'], Http::form($calls[1]['body'])['client_assertion']);
    }

    public function testASecondNonceChallengeAtTheTokenEndpointFails(): void
    {
        $challenge = Http::response(400, ['error' => 'use_dpop_nonce', 'error_description' => 'nonce'], ['DPoP-Nonce' => 'n']);
        $this->http->then($challenge)->then($challenge);

        $this->expectException(AuthenticationException::class);
        $this->client()->request('GET', '/api/v1/me');
    }

    public function testApiNonceChallengeIsRetriedWithTheSameBodyAndIdempotencyKey(): void
    {
        $challenge = Http::response(401, ['error' => 'use_dpop_nonce', 'error_description' => 'x'], [
            'WWW-Authenticate' => 'DPoP error="use_dpop_nonce", error_description="Resource server requires a DPoP nonce.", algs="ES256"',
            'DPoP-Nonce' => 'api-nonce-1',
        ]);
        $this->http->then(Http::token())->then($challenge)->then(Http::response(201, ['success' => true, 'data' => ['id' => 'env_1']]));

        $data = $this->client()->request('POST', '/api/v1/envelopes', ['json' => ['name' => 'NDA'], 'idempotencyKey' => 'idem-1'])->data();

        self::assertSame(['id' => 'env_1'], $data);
        $calls = $this->http->sentTo('/api/v1/envelopes');
        self::assertCount(2, $calls);
        self::assertSame($calls[0]['body'], $calls[1]['body']);
        self::assertSame('{"name":"NDA"}', $calls[1]['body']);
        self::assertSame('idem-1', $calls[1]['request']->getHeaderLine('Idempotency-Key'));
        self::assertSame('api-nonce-1', JwtAssert::verified($calls[1]['request']->getHeaderLine('DPoP'))['claims']['nonce']);
    }

    public function testRejectedTokenIsReplacedOnceAndTheRequestReplayed(): void
    {
        $invalid = Http::response(401, ['error' => 'invalid_token', 'error_description' => 'revoked'], [
            'WWW-Authenticate' => 'DPoP error="invalid_token", error_description="revoked", algs="ES256"',
        ]);
        $this->http
            ->then(Http::token('tok-old'))
            ->then($invalid)
            ->then(Http::token('tok-new'))
            ->then(Http::response(200, ['success' => true, 'data' => []]));

        $this->client()->request('POST', '/api/v1/envelopes', ['json' => [], 'idempotencyKey' => 'idem-2']);

        $calls = $this->http->sentTo('/api/v1/envelopes');
        self::assertSame('DPoP tok-new', $calls[1]['request']->getHeaderLine('Authorization'));
        self::assertSame('idem-2', $calls[1]['request']->getHeaderLine('Idempotency-Key'));
    }

    public function testATokenRejectedTwiceFailsWithAuthenticationException(): void
    {
        $invalid = Http::response(401, ['error' => 'invalid_token', 'error_description' => 'revoked'], [
            'WWW-Authenticate' => 'DPoP error="invalid_token", algs="ES256"',
        ]);
        $this->http->then(Http::token('a'))->then($invalid)->then(Http::token('b'))->then($invalid);

        try {
            $this->client()->request('GET', '/api/v1/me');
            self::fail('expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame('invalid_token', $e->errorCode);
            self::assertSame(401, $e->statusCode);
        }
    }

    public function testTokensAreReusedAndRenewedShortlyBeforeExpiry(): void
    {
        $ok = Http::response(200, ['success' => true, 'data' => []]);
        $this->http->then(Http::token('tok-1', 900))->then($ok)->then($ok)->then(Http::token('tok-2', 900))->then($ok);
        $client = $this->client();

        $client->request('GET', '/api/v1/me');
        $this->clock->advance(839); // 61 s left: still reused
        $client->request('GET', '/api/v1/me');
        $this->clock->advance(1); // 60 s left: renewed
        $client->request('GET', '/api/v1/me');

        self::assertCount(2, $this->http->sentTo('/oauth/token'));
        $this->http->assertDone();
    }

    public function testWorkersSharingACacheShareTheToken(): void
    {
        $cache = new ArrayCache($this->clock);
        $ok = Http::response(200, ['success' => true, 'data' => []]);
        $this->http->then(Http::token())->then($ok)->then($ok);

        $this->client(cache: $cache)->request('GET', '/api/v1/me');
        $this->client(cache: $cache)->request('GET', '/api/v1/me');

        self::assertCount(1, $this->http->sentTo('/oauth/token'));
    }

    public function testTheCacheHoldsNoKeyMaterial(): void
    {
        $cache = new ArrayCache($this->clock);
        $this->http->then(Http::token())->then(Http::response(200, ['success' => true, 'data' => []]));

        $this->client(cache: $cache)->request('GET', '/api/v1/me');

        $dump = print_r($cache, true);
        foreach ([$this->clientKey, $this->dpopKey] as $key) {
            $private = json_decode($key->exportPrivateJwk(), true);
            self::assertIsArray($private);
            self::assertStringNotContainsString($private['d'], $dump);
        }
    }

    public function testAWorkerThatCannotTakeTheLockWaitsForTheOtherWorkersToken(): void
    {
        $cache = new ArrayCache($this->clock);
        $lock = new class implements TokenLock {
            public function acquire(string $key, int $ttlSeconds): bool
            {
                return false;
            }

            public function release(string $key): void
            {
            }
        };
        // While this worker waits, "another worker" stores a token.
        $other = $this->client(cache: $cache);
        $this->http->then(Http::token('from-other-worker'))->then(Http::response(200, ['success' => true, 'data' => []]));
        $sleep = function () use ($other): void {
            static $done = false;
            if (!$done) {
                $done = true;
                $other->authenticate();
            }
        };

        $this->client(cache: $cache, lock: $lock, sleep: $sleep)->request('GET', '/api/v1/me');

        $calls = $this->http->sentTo('/api/v1/me');
        self::assertSame('DPoP from-other-worker', $calls[0]['request']->getHeaderLine('Authorization'));
    }

    public function testSafeRequestsAreRetriedWithBackoff(): void
    {
        $this->http
            ->then(Http::token())
            ->then(Http::apiError(503, 'INTERNAL_ERROR'))
            ->then(fn (RequestInterface $r) => Http::networkError($r))
            ->then(Http::response(200, ['success' => true, 'data' => ['ok' => true]]));

        $data = $this->client()->request('GET', '/api/v1/me')->data();

        self::assertSame(['ok' => true], $data);
        self::assertCount(2, $this->sleeps);
        $proofs = array_map(fn ($c) => JwtAssert::verified($c['request']->getHeaderLine('DPoP'))['claims']['jti'], $this->http->sentTo('/api/v1/me'));
        self::assertCount(3, array_unique($proofs), 'each attempt has its own proof');
    }

    public function testPostWithoutIdempotencyKeyIsNeverRetried(): void
    {
        $this->http->then(Http::token())->then(Http::apiError(503, 'INTERNAL_ERROR'));

        $this->expectException(ServerException::class);
        try {
            $this->client()->request('POST', '/api/v1/documents', ['json' => ['name' => 'x']]);
        } finally {
            self::assertSame([], $this->sleeps);
        }
    }

    public function testPostWithoutIdempotencyKeyIsNotResentAfterANetworkError(): void
    {
        $this->http->then(Http::token())->then(fn (RequestInterface $r) => Http::networkError($r));

        $this->expectException(TransportException::class);
        $this->client()->request('POST', '/api/v1/documents', ['json' => ['name' => 'x']]);
    }

    public function testPostWithIdempotencyKeyIsRetriedAndWaitsForAnInProgressOriginal(): void
    {
        $this->http
            ->then(Http::token())
            ->then(Http::apiError(409, 'IDEMPOTENCY_IN_PROGRESS', 'req-9', ['Retry-After' => '2']))
            ->then(Http::response(201, ['success' => true, 'data' => ['id' => 'env_1']]));

        $this->client()->request('POST', '/api/v1/envelopes', ['json' => [], 'idempotencyKey' => 'k']);

        self::assertSame([2_000_000], $this->sleeps);
    }

    public function testRetryAfterIsHonouredAndTooLongWaitsAreNotTaken(): void
    {
        $this->http
            ->then(Http::token())
            ->then(Http::apiError(429, 'RATE_LIMITED', 'r1', ['Retry-After' => '3']))
            ->then(Http::apiError(429, 'RATE_LIMITED', 'r2', ['Retry-After' => '120']));

        try {
            $this->client()->request('GET', '/api/v1/me');
            self::fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertSame(120, $e->retryAfter);
            self::assertSame('r2', $e->requestId);
        }
        self::assertSame([3_000_000], $this->sleeps);
    }

    public function testRetriesStopAfterMaxAttempts(): void
    {
        $this->http->then(Http::token())
            ->then(Http::apiError(502, 'INTERNAL_ERROR'))
            ->then(Http::apiError(502, 'INTERNAL_ERROR'))
            ->then(Http::apiError(502, 'INTERNAL_ERROR'));

        $this->expectException(ServerException::class);
        $this->client()->request('GET', '/api/v1/me');
    }

    public function testTokenEndpointOutagesAreRetriedEvenForPosts(): void
    {
        $this->http
            ->then(Http::response(503, ['error' => 'temporarily_unavailable', 'error_description' => 'redis'], ['Retry-After' => '1']))
            ->then(Http::token())
            ->then(Http::response(201, ['success' => true, 'data' => []]));

        $this->client()->request('POST', '/api/v1/documents', ['json' => []]);

        self::assertSame([1_000_000], $this->sleeps);
        self::assertCount(1, $this->http->sentTo('/api/v1/documents'));
    }

    public function testApiErrorsMapToTypedExceptions(): void
    {
        $this->http->then(Http::token())
            ->then(Http::apiError(404, 'NOT_FOUND', 'req-404'))
            ->then(Http::apiError(409, 'IDEMPOTENCY_KEY_REUSED', 'req-409'))
            ->then(Http::response(403, ['error' => 'insufficient_scope', 'error_description' => 'needs signing:create'], [
                'WWW-Authenticate' => 'DPoP error="insufficient_scope", scope="signing:create", algs="ES256"',
            ]));
        $client = $this->client(retry: RetryPolicy::none());

        $this->assertThrows(NotFoundException::class, fn () => $client->request('GET', '/api/v1/envelopes/x'), function (NotFoundException $e): void {
            self::assertSame('NOT_FOUND', $e->errorCode);
            self::assertSame('req-404', $e->requestId);
            self::assertStringContainsString('req-404', $e->getMessage());
        });
        $this->assertThrows(IdempotencyConflictException::class, fn () => $client->request('POST', '/api/v1/envelopes', ['json' => [], 'idempotencyKey' => 'k']));
        $this->assertThrows(PermissionException::class, fn () => $client->request('POST', '/api/v1/envelopes', ['json' => []]), function (PermissionException $e): void {
            self::assertSame('insufficient_scope', $e->errorCode);
            self::assertSame('needs signing:create', $e->getMessage());
        });
    }

    public function testFailuresNeverLogTokensOrAssertions(): void
    {
        $this->http
            ->then(Http::response(401, ['error' => 'invalid_client', 'error_description' => 'Client authentication failed.']));

        try {
            $this->client()->request('GET', '/api/v1/me');
            self::fail('expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame('invalid_client', $e->errorCode);
        }

        $form = Http::form($this->http->sent[0]['body']);
        $logged = $this->logger->serialized();
        self::assertStringNotContainsString($form['client_assertion'], $logged);
        self::assertStringNotContainsString($this->http->sent[0]['request']->getHeaderLine('DPoP'), $logged);
        self::assertStringContainsString('invalid_client', $logged);
    }

    public function testRefusesTheSameKeyForClientAuthenticationAndDpop(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('different key');
        $this->dpopKey = $this->clientKey;
        $this->client();
    }

    /**
     * @template T of \Throwable
     * @param class-string<T> $class
     * @param (\Closure(T): void)|null $inspect
     */
    private function assertThrows(string $class, \Closure $call, ?\Closure $inspect = null): void
    {
        try {
            $call();
        } catch (\Throwable $e) {
            self::assertInstanceOf($class, $e);
            if ($inspect !== null) {
                $inspect($e);
            }

            return;
        }
        self::fail("expected $class");
    }

    private function client(
        ?CacheInterface $cache = null,
        ?TokenLock $lock = null,
        ?RetryPolicy $retry = null,
        ?\Closure $sleep = null,
    ): Client {
        $factory = new Psr17Factory();

        return new Client(
            environment: self::BASE,
            clientId: self::CLIENT_ID,
            clientKey: $this->clientKey,
            dpopKey: $this->dpopKey,
            scopes: ['documents:read', 'signing:create'],
            cache: $cache,
            tokenLock: $lock,
            httpClient: $this->http,
            requestFactory: $factory,
            streamFactory: $factory,
            logger: $this->logger,
            retryPolicy: $retry,
            clock: $this->clock,
            sleep: $sleep ?? function (int $microseconds): void {
                $this->sleeps[] = $microseconds;
            },
        );
    }
}
