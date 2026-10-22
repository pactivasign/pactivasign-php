# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- OAuth 2.0 client credentials with `private_key_jwt` client authentication and DPoP-bound access tokens.
- Key providers: `LocalKey` (JWK or PEM file, JWK array, environment variable, generated) and
  `CallbackKey` for keys held in a KMS or vault.
- Token caching through any PSR-16 cache, with an optional cross-worker refresh lock (`TokenLock`).
- DPoP nonce handling at the token endpoint and the API.
- Automatic re-authentication when the API rejects a token.
- Retries with backoff for safe requests (GET, HEAD, and requests with an Idempotency-Key).
- Typed exceptions mapped from the API's error codes.
- `Client::request()` for any API path, with JSON, multipart and raw bodies.
