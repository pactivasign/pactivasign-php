# PactivaSign PHP SDK

The official PHP client for the [PactivaSign](https://github.com/pactivasign) e-signature API.

> **Status: pre-release.** Until 1.0, minor versions may change the API. Built against API contract
> `openapi-v1.0.0`.

- OAuth 2.0 client credentials with `private_key_jwt` (RFC 7523): no shared secrets.
- DPoP-bound access tokens (RFC 9449): a leaked token is useless without your DPoP key.
- Token caching across PHP workers, DPoP nonce handling, automatic re-authentication.
- Safe retries: only GET/HEAD and requests with an Idempotency-Key are ever resent.
- A method for every API operation, with typed, read-only response models.
- Lists that follow cursors for you; PDF downloads streamed to disk.
- Webhook signature verification, with secret rotation.
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
    scopes: ['documents:create', 'documents:read', 'documents:download', 'signing:create', 'signing:read'],
    cache: $psr16Cache,                             // share tokens across PHP workers
    logger: $logger,                                // optional PSR-3
);

$me = $pactiva->me->get();                          // organization, granted scopes, plan limits
```

### Send a document for signature

```php
$document = $pactiva->documents->upload('NDA', fopen('nda.pdf', 'rb'), 'nda.pdf');

$envelope = $pactiva->envelopes->create([
    'name' => 'NDA with Acme',
    'documentIds' => [$document->id],
    'signers' => [
        ['email' => 'jo@example.com', 'name' => 'Jo Bloggs'],
    ],
    'externalId' => 'deal-42',                      // your own reference; filter on it later
]);

echo $envelope->status->value;                      // "SENT"
```

`envelopes->create()` sends an `Idempotency-Key`, so a retried call never sends the envelope twice. Pass your
own with `idempotencyKey: "order-42-nda"` if your code may call `create()` again after a crash.

Request bodies are arrays. Their exact shape is in each method's PHPDoc, so your IDE and PHPStan check
field names and types. Dates accept `DateTimeInterface` where the API takes a date-time, and enum
fields accept the enum's cases.

### Resources

| Property | Operations |
| --- | --- |
| `$pactiva->envelopes` | `list`, `create`, `get`, `downloadSigned`, `updateRecipient`, `resend`, `void` |
| `$pactiva->documents` | `list`, `create`, `upload`, `get`, `rename`, `delete`, `uploadFile`, `downloadFile`, `getAuditTrail`, `downloadAuditReport`, `listFields`, `createField`, `updateField`, `deleteField`, `listRecipients`, `replaceRecipients` |
| `$pactiva->signingRequests` | `create`, `getLatest`, `resend` |
| `$pactiva->templates` | `list`, `get`, `listRoles`, `createDocument` |
| `$pactiva->webhookSubscriptions` | `list`, `create`, `get`, `update`, `delete`, `rotateSecret`, `sendTest` |
| `$pactiva->webhookDeliveries` | `list`, `redeliver` |
| `$pactiva->me` | `get` |

Each method's PHPDoc names the OAuth scopes it needs. For anything else, `$pactiva->request($method, $path, $options)`
sends an authenticated request to any path.

### Lists

`list()` returns a paginator. Nothing is fetched until you iterate, and further pages are fetched as you go:

```php
foreach ($pactiva->envelopes->list(['status' => EnvelopeStatus::Sent, 'createdFrom' => new DateTime('-7 days')]) as $envelope) {
    // every matching envelope, page after page
}

$page = $pactiva->envelopes->list(['limit' => 20])->firstPage();   // one page: $page->items, $page->nextCursor
```

### Downloads

```php
$pdf = $pactiva->envelopes->downloadSigned($envelope->id);
$pdf->saveTo("/var/contracts/{$envelope->id}.pdf");      // streamed in chunks

// Next time, skip the download if nothing changed:
$again = $pactiva->envelopes->downloadSigned($envelope->id, ifNoneMatch: $pdf->etag());
if ($again->isNotModified()) { /* your copy is current */ }
```

### Models

Responses are read-only objects with typed properties: dates are `DateTimeImmutable`, statuses are enums
(`EnvelopeStatus::Completed`), and nested objects are models too. The API adds fields and enum values
over time without a new version. Unknown enum values become the `Unknown` case instead of an error, and
`$model->toArray()` returns the raw JSON, including fields this SDK version does not know yet.

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
| `SignatureVerificationException` | A webhook signature is missing, wrong or too old |
| `UnexpectedResponseException` | A response did not match the API contract |

API exceptions carry `$e->errorCode`, `$e->statusCode`, `$e->details` and `$e->requestId`. Quote the
request ID when you contact support.

### Retries

GET and HEAD requests, and requests sent with an `idempotencyKey`, are retried up to 3 times on network
errors, 429, 502, 503 and 504, with exponential backoff and jitter. `Retry-After` is honoured up to 60
seconds. Other POST, PATCH and DELETE requests are never resent automatically, so nothing is created twice.
Tune or disable this with `retryPolicy: new RetryPolicy(...)` or `RetryPolicy::none()`.

## Webhooks

Create a subscription once and store its secret:

```php
$subscription = $pactiva->webhookSubscriptions->create([
    'url' => 'https://example.com/pactivasign/webhook',
    'eventTypes' => ['envelope.completed', 'envelope.declined', 'recipient.signed'],
]);
$secret = $subscription->secret;                    // shown only now and after rotateSecret()
```

Then verify every delivery against the **raw** request body before trusting it:

```php
use PactivaSign\Exception\SignatureVerificationException;
use PactivaSign\Model\WebhookEventType;
use PactivaSign\Webhook\Webhook;

try {
    $event = Webhook::verify(
        file_get_contents('php://input'),
        $_SERVER['HTTP_PACTIVA_SIGNATURE'] ?? '',
        [$secret, $previousSecret],                 // both, while a rotation is in progress
    );
} catch (SignatureVerificationException) {
    http_response_code(400);
    exit;
}

// Deliveries can repeat and arrive out of order:
// skip event IDs you have seen, and events whose data->version is older than what you stored.
if ($event->type === WebhookEventType::EnvelopeCompleted) {
    $pactiva->envelopes->downloadSigned($event->data->envelopeId)->saveTo("/var/contracts/{$event->data->envelopeId}.pdf");
}
http_response_code(200);
```

Signatures older than 5 minutes are rejected, against replays. `Webhook::signatureHeader()` builds a valid
header, for testing your endpoint.

## Development

```bash
composer install
composer check          # PHPStan level 8 and unit tests
composer generate       # regenerate src/Model and src/Resource from openapi/public.json
```

Models, enums and resource classes are generated from the API contract in `openapi/public.json`, copied
from the API repository's `openapi-v*` tag recorded in `openapi/SOURCE.json`. Don't edit generated files
(they say so at the top); change the generator in `tools/generate.php` or update the spec. A unit test fails
when the generated code is stale.

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
