<?php

declare(strict_types=1);

namespace PactivaSign;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PactivaSign\Auth\ClientAssertionFactory;
use PactivaSign\Auth\DpopProofFactory;
use PactivaSign\Auth\NonceStore;
use PactivaSign\Auth\NullTokenLock;
use PactivaSign\Auth\TokenLock;
use PactivaSign\Auth\TokenProvider;
use PactivaSign\Cache\ArrayCache;
use PactivaSign\Exception\ConfigurationException;
use PactivaSign\Http\Multipart;
use PactivaSign\Http\Response;
use PactivaSign\Http\RetryPolicy;
use PactivaSign\Http\Transport;
use PactivaSign\Key\Jwk;
use PactivaSign\Key\KeyProvider;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * PactivaSign API client.
 *
 *     $pactiva = new PactivaSign\Client(
 *         environment: PactivaSign\Environment::staging(),
 *         clientId: 'pcl_...',
 *         clientKey: PactivaSign\Key\LocalKey::fromFile('/run/secrets/pactiva-client.jwk'),
 *         dpopKey: PactivaSign\Key\LocalKey::fromFile('/run/secrets/pactiva-dpop.jwk'),
 *         scopes: ['documents:read', 'signing:create'],
 *         cache: $psr16Cache,
 *     );
 *     $me = $pactiva->request('GET', '/api/v1/me')->data();
 *
 * Pass a shared PSR-16 cache in production, so every PHP worker reuses the same access token.
 */
final class Client
{
    public const VERSION = '0.1.0-dev';

    private readonly Environment $environment;
    private readonly TokenProvider $tokens;
    private readonly Transport $transport;

    /**
     * @param list<string> $scopes the scopes to request; must be a subset of the client's allowed scopes
     */
    public function __construct(
        Environment|string $environment,
        string $clientId,
        KeyProvider $clientKey,
        KeyProvider $dpopKey,
        array $scopes,
        ?CacheInterface $cache = null,
        ?TokenLock $tokenLock = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null,
        ?RetryPolicy $retryPolicy = null,
        ?ClockInterface $clock = null,
        ?\Closure $sleep = null,
    ) {
        if (!str_starts_with($clientId, 'pcl_')) {
            throw new ConfigurationException('The client ID should start with "pcl_".');
        }
        if ($scopes === []) {
            throw new ConfigurationException('Request at least one scope; PactivaSign issues no token without scopes.');
        }
        if (Jwk::thumbprint($clientKey->publicJwk()) === Jwk::thumbprint($dpopKey->publicJwk())) {
            throw new ConfigurationException('The DPoP key must be a different key from the client authentication key.');
        }

        $this->environment = is_string($environment) ? new Environment($environment) : $environment;
        $clock ??= new SystemClock();
        $cache ??= new ArrayCache($clock);
        $logger ??= new NullLogger();
        $httpClient ??= Psr18ClientDiscovery::find();
        $requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();
        $userAgent = sprintf('pactivasign-php/%s PHP/%s', self::VERSION, PHP_VERSION);

        $proofs = new DpopProofFactory($dpopKey, $clock);
        $nonces = new NonceStore($cache, 'pactivasign.' . hash('sha256', $this->environment->baseUrl . "\n" . $clientId));

        $this->tokens = new TokenProvider(
            $this->environment,
            $clientId,
            array_values(array_unique($scopes)),
            new ClientAssertionFactory($clientId, $clientKey, $this->environment->baseUrl, $clock),
            $proofs,
            $nonces,
            $httpClient,
            $requestFactory,
            $streamFactory,
            $cache,
            $tokenLock ?? new NullTokenLock(),
            $clock,
            $logger,
            $userAgent,
            $sleep,
        );

        $this->transport = new Transport(
            $this->environment,
            $this->tokens,
            $proofs,
            $nonces,
            $httpClient,
            $requestFactory,
            $streamFactory,
            $retryPolicy ?? new RetryPolicy(),
            $logger,
            $userAgent,
            $sleep,
        );
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    /**
     * Sends an authenticated request to any API path, e.g. `request('GET', '/api/v1/me')`.
     *
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
    public function request(string $method, string $path, array $options = []): Response
    {
        return $this->transport->send($method, $path, $options);
    }

    /** Fetches a token now, e.g. to check credentials at deploy time. Returns the granted scopes. */
    public function authenticate(): string
    {
        return $this->tokens->token()->scope;
    }

    /** Drops the cached access token; the next request authenticates again. */
    public function forgetToken(): void
    {
        $this->tokens->forget();
    }
}
