# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.2.0] - 2026-11-25

Pre-1.0 minor release with breaking changes: the API renamed Organization to Workspace (contract
`openapi-v2.0.0`).

### Changed

- **Breaking:** every `organizationId` property is now `workspaceId`, on documents, envelopes, templates,
  signing requests, audit events, sandbox emails and webhook events.
- **Breaking:** `$pactiva->me->get()` returns `$me->workspace` (a `MeWorkspace`) instead of
  `$me->organization`, and `OrganizationStatus` is now `WorkspaceStatus`.
- `AuditEventType` gains `WorkspaceSettingsUpdated` and `WorkspaceStatusChanged`. Events recorded before the
  rename keep `OrganizationSettingsUpdated` and `OrganizationStatusChanged`, so handle both.

### Added

- Several workspaces per client: a `workspaceId` constructor argument and `$pactiva->withWorkspace($id)` send
  `X-Workspace-Id`, and `$me->workspaces` lists the workspaces the client is approved for, with its scopes in each.
  `$me->accountId` names the account. `ErrorCode::WorkspaceRequired` when a choice is needed.
- `WebhookEvent::$accountId`, alongside `workspaceId`.
- `ErrorCode::CaptchaFailed` and `ErrorCode::EmailNotAllowed` (sign-up checks), and `PlanCode::FreeLegacy` for
  accounts that keep the Free limits they had before the new Free plan.
- Models added to the API since 0.1.0, including signing corrections (`SigningCorrection`,
  `CorrectionRecipient`, `CorrectionField`) and `DocumentEnvelope`.

## [0.1.0] - 2026-10-26

First public release. Pre-1.0: minor versions may contain breaking changes.

### Added

- Typed methods for all 41 operations of API contract `openapi-v1.1.1`: `$pactiva->envelopes`, `documents`,
  `signingRequests`, `templates`, `webhookSubscriptions`, `webhookDeliveries`, `me` and `sandbox`.
- `Environment::sandbox()` for the public sandbox, and `$pactiva->sandbox->listEmails()` to read the email
  it captures (signing links and one-time codes) in tests.
- Read-only response models with `DateTimeImmutable` dates, enums with an `Unknown` case for values added
  later, and `toArray()` returning the raw JSON.
- Request bodies as arrays with exact PHPStan shapes; `DateTimeInterface` and enum cases accepted.
- Lazy cursor pagination (`Paginator`, `Page`) and streamed downloads with ETag support (`Download`).
- Automatic `Idempotency-Key` on `envelopes->create()`.
- Webhook signature verification (`Webhook::verify()`), with secret rotation and replay tolerance.
- Code generator (`composer generate`) that builds models and resources from `openapi/public.json`.
- OAuth 2.0 client credentials with `private_key_jwt` client authentication and DPoP-bound access tokens.
- Key providers: `LocalKey` (JWK or PEM file, JWK array, environment variable, generated) and
  `CallbackKey` for keys held in a KMS or vault.
- Token caching through any PSR-16 cache, with an optional cross-worker refresh lock (`TokenLock`).
- DPoP nonce handling at the token endpoint and the API.
- Automatic re-authentication when the API rejects a token.
- Retries with backoff for safe requests (GET, HEAD, and requests with an Idempotency-Key).
- Typed exceptions mapped from the API's error codes.
- `Client::request()` for any API path, with JSON, multipart and raw bodies.

[Unreleased]: https://github.com/pactivasign/pactivasign-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/pactivasign/pactivasign-php/releases/tag/v0.1.0
