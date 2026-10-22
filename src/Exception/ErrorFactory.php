<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Turns an error response into the matching exception.
 *
 * Two body shapes exist: the API envelope `{"success": false, "error": {code, message, requestId, details}}`
 * and, for authentication failures, the RFC 6749 object `{"error": "...", "error_description": "..."}`.
 *
 * @internal
 */
final class ErrorFactory
{
    private const API_CODES = [
        'VALIDATION_ERROR' => ValidationException::class,
        'UNAUTHENTICATED' => AuthenticationException::class,
        'INVALID_CREDENTIALS' => AuthenticationException::class,
        'TOKEN_INVALID' => AuthenticationException::class,
        'TOKEN_EXPIRED' => AuthenticationException::class,
        'FORBIDDEN' => PermissionException::class,
        'DOCUMENT_ACCESS_DENIED' => PermissionException::class,
        'TENANT_REQUIRED' => PermissionException::class,
        'NOT_FOUND' => NotFoundException::class,
        'CONFLICT' => ConflictException::class,
        'INVALID_STATE' => ConflictException::class,
        'NOT_READY' => ConflictException::class,
        'IDEMPOTENCY_IN_PROGRESS' => IdempotencyConflictException::class,
        'IDEMPOTENCY_KEY_REUSED' => IdempotencyConflictException::class,
        'RATE_LIMITED' => RateLimitException::class,
        'PLAN_LIMIT_EXCEEDED' => PlanLimitException::class,
        'SUBSCRIPTION_INACTIVE' => PlanLimitException::class,
        'INTERNAL_ERROR' => ServerException::class,
    ];

    private const OAUTH_CODES = [
        'insufficient_scope' => PermissionException::class,
        'invalid_scope' => PermissionException::class,
        'unauthorized_client' => PermissionException::class,
        'slow_down' => RateLimitException::class,
        'temporarily_unavailable' => ServerException::class,
        'invalid_request' => ValidationException::class,
        'unsupported_grant_type' => ValidationException::class,
    ];

    public static function fromResponse(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $requestId = $response->getHeaderLine('X-Request-Id') ?: null;
        $body = self::decode($response);

        if (isset($body['error']) && is_array($body['error'])) {
            $error = $body['error'];
            $code = is_string($error['code'] ?? null) ? $error['code'] : 'UNKNOWN';
            $message = is_string($error['message'] ?? null) ? $error['message'] : self::fallbackMessage($status);
            $requestId = is_string($error['requestId'] ?? null) ? $error['requestId'] : $requestId;
            $details = is_array($error['details'] ?? null) ? $error['details'] : [];
            $class = self::API_CODES[$code] ?? self::byStatus($status);
        } elseif (isset($body['error']) && is_string($body['error'])) {
            $code = $body['error'];
            $message = is_string($body['error_description'] ?? null) && $body['error_description'] !== ''
                ? $body['error_description']
                : $code;
            $details = [];
            $class = self::OAUTH_CODES[$code] ?? ($status >= 500 ? ServerException::class : AuthenticationException::class);
        } else {
            $code = self::oauthCodeFromChallenge($response) ?? 'UNKNOWN';
            $message = self::fallbackMessage($status);
            $details = [];
            $class = self::OAUTH_CODES[$code] ?? self::byStatus($status);
        }

        // The API code wins over the status, except that a 5xx is always a server failure.
        if ($status >= 500 && !is_a($class, ServerException::class, true) && $class !== RateLimitException::class) {
            $class = ServerException::class;
        }

        if ($class === RateLimitException::class) {
            return new RateLimitException($message, $status, $code, $requestId, $details, $response, self::retryAfter($response));
        }

        return new $class($message, $status, $code, $requestId, $details, $response);
    }

    /** Seconds from a Retry-After header (delta-seconds or HTTP date), or null when absent or unparsable. */
    public static function retryAfter(ResponseInterface $response, ?int $now = null): ?int
    {
        $value = trim($response->getHeaderLine('Retry-After'));
        if ($value === '') {
            return null;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? null : max(0, $timestamp - ($now ?? time()));
    }

    /** The `error` parameter of a `WWW-Authenticate: DPoP ...` challenge, if any. */
    public static function oauthCodeFromChallenge(ResponseInterface $response): ?string
    {
        foreach ($response->getHeader('WWW-Authenticate') as $challenge) {
            if (preg_match('/\berror="([^"]*)"/', $challenge, $match) === 1) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(ResponseInterface $response): array
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $decoded = json_decode((string) $body, true);
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return class-string<ApiException>
     */
    private static function byStatus(int $status): string
    {
        return match (true) {
            $status === 400, $status === 422 => ValidationException::class,
            $status === 401 => AuthenticationException::class,
            $status === 403 => PermissionException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => ApiException::class,
        };
    }

    private static function fallbackMessage(int $status): string
    {
        return sprintf('PactivaSign API returned HTTP %d.', $status);
    }
}
