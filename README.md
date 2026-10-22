# PactivaSign PHP SDK

The official PHP client for the [PactivaSign](https://github.com/pactivasign) e-signature API.

> **Status: pre-release.** This version contains the authentication and transport layer. Typed resources
> (`$pactiva->envelopes->create(...)`), models and webhook verification come next. Until 1.0, minor
> versions may change the API.

- OAuth 2.0 client credentials with `private_key_jwt` (RFC 7523): no shared secrets.
- DPoP-bound access tokens (RFC 9449): a leaked token is useless without your DPoP key.
- Token caching across PHP workers, DPoP nonce handling, automatic re-authentication.
- Safe retries: only GET/HEAD and requests with an Idempotency-Key are ever resent.
- Typed exceptions that carry the API error code and the request ID.

Requires PHP 8.2+ and any PSR-18 HTTP client (Guzzle, Symfony HttpClient, …).

## Installation

```bash
composer require pactivasign/pactivasign-php
```

If your project has no PSR-18 client yet, add one, for example `composer require guzzlehttp/guzzle`.

## Keys

The SDK uses **two different key pairs**:

| Key | Used for | Registered with PactivaSign? |
| --- | --- | --- |
| Client key | Signing the client assertion that proves who you are | Yes, the public half |
| DPoP key | Signing a proof on every request; tokens are bound to it | No, but it must stay the same across workers and deploys |

Generate both once and keep the private halves in a secret manager or in files with mode `0600`:

```php
use PactivaSign\Key\LocalKey;

$clientKey = LocalKey::generate('ES256', keyId: 'my-app-2026-10');
file_put_contents('client.jwk', $clientKey->exportPrivateJwk());
chmod('client.jwk', 0600);

$dpopKey = LocalKey::generate('ES256');
file_put_contents('dpop.jwk', $dpopKey->exportPrivateJwk());
chmod('dpop.jwk', 0600);

// Give this to your PactivaSign organization admin to register the OAuth client:
echo json_encode($clientKey->registrationJwk(), JSON_PRETTY_PRINT);
```

Supported algorithms: ES256, ES384, ES512, PS256, PS384, PS512, RS256, RS384, RS512 and EdDSA (Ed25519).
`LocalKey::fromFile()` also loads PEM private keys.

### Keys in a KMS or vault

To keep the client key inside AWS KMS, Azure Key Vault or HashiCorp Vault, sign through a callback:

```php
use PactivaSign\Key\CallbackKey;

$clientKey = new CallbackKey(
    algorithm: 'ES256',
    publicJwk: $publicJwk,           // kty, crv, x, y
    signer: fn (string $input) => CallbackKey::derToJose($kms->sign($input), 32),
    keyId: 'my-app-2026-10',
);
```

## Usage

```php
use PactivaSign\Client;
use PactivaSign\Environment;
use PactivaSign\Key\LocalKey;

$pactiva = new Client(
    environment: Environment::staging(),           // or 'https://your-api-host'
    clientId: 'pcl_...',
    clientKey: LocalKey::fromFile('/run/secrets/pactivasign-client.jwk', logger: $logger),
    dpopKey: LocalKey::fromFile('/run/secrets/pactivasign-dpop.jwk'),
    scopes: ['documents:create', 'documents:read', 'signing:create', 'signing:read'],
    cache: $psr16Cache,                             // share tokens across PHP workers
    logger: $logger,                                // optional PSR-3
);

$me = $pactiva->request('GET', '/api/v1/me')->data();

$envelopes = $pactiva->request('GET', '/api/v1/envelopes', ['query' => ['limit' => 20]])->data();

$envelope = $pactiva->request('POST', '/api/v1/envelopes', [
    'json' => ['name' => 'NDA', 'documentIds' => [$documentId], 'signers' => [['email' => 'jo@example.com', 'name' => 'Jo']]],
    'idempotencyKey' => $orderId . '-nda',         // reuse it if you resend the same request
])->data();
```

Uploads use `PactivaSign\Http\Multipart`:

```php
use PactivaSign\Http\Multipart;

$document = $pactiva->request('POST', '/api/v1/documents/upload', [
    'multipart' => (new Multipart())
        ->field('name', 'Contract')
        ->file('file', fopen('contract.pdf', 'rb'), 'contract.pdf'),
])->data();
```

### Caching and concurrency

Without a `cache`, each PHP request fetches its own token. In production pass a shared PSR-16 cache
(Redis, Memcached, APCu, your framework's cache). Tokens are cached per environment, client ID, scope set and
DPoP key, and renewed 60 seconds before they expire. The cache never holds private keys.

To make sure only one worker renews the token at a time, implement `PactivaSign\Auth\TokenLock` with your
cache's atomic lock and pass it as `tokenLock`. Without it, workers that find the cache empty each fetch a
token, which is correct but uses more requests.

### Errors

Every exception implements `PactivaSign\Exception\PactivaException`.

| Exception | When |
| --- | --- |
| `ValidationException` | 400 or 422: the request was invalid |
| `AuthenticationException` | 401, or the token endpoint refused the client |
| `PermissionException` | 403, including a scope the client was not granted |
| `NotFoundException` | 404 (also for another organization's resources) |
| `ConflictException` | 409: the resource is in the wrong state |
| `IdempotencyConflictException` | The Idempotency-Key was reused for a different request |
| `RateLimitException` | 429; see `$e->retryAfter` |
| `PlanLimitException` | The organization's plan does not allow this |
| `ServerException` | 5xx |
| `TransportException` | No response at all (DNS, TLS, timeout) |
| `ConfigurationException` | The client was set up wrongly |

API exceptions carry `$e->errorCode`, `$e->statusCode`, `$e->details` and `$e->requestId`. Quote the
request ID when you contact support.

### Retries

GET and HEAD requests, and requests sent with an `idempotencyKey`, are retried up to 3 times on network
errors, 429, 502, 503 and 504, with exponential backoff and jitter. `Retry-After` is honoured up to 60
seconds. Other POST, PATCH and DELETE requests are never resent automatically, so nothing is created twice.
Tune or disable this with `retryPolicy: new RetryPolicy(...)` or `RetryPolicy::none()`.

## Development

```bash
composer install
composer check          # PHPStan level 8 and unit tests
```

The integration tests run against a live API and are skipped unless these are set:
`PACTIVASIGN_BASE_URL`, `PACTIVASIGN_CLIENT_ID`, `PACTIVASIGN_CLIENT_KEY_FILE`, `PACTIVASIGN_DPOP_KEY_FILE`
and `PACTIVASIGN_SCOPES` (see `tests/Integration/LiveApiTest.php`).

```bash
composer test:integration
```

## Security

See [SECURITY.md](SECURITY.md). Never commit key files; `*.jwk` and `*.pem` are ignored by this repository.

## Licence

MIT. See [LICENSE](LICENSE).
