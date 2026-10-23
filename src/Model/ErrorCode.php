<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

/**
 * Closed list; new codes are added in minor versions, so treat unknown ones as generic errors.
 */
enum ErrorCode: string
{
    case ValidationError = 'VALIDATION_ERROR';
    case Unauthenticated = 'UNAUTHENTICATED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case Conflict = 'CONFLICT';
    case RateLimited = 'RATE_LIMITED';
    case InternalError = 'INTERNAL_ERROR';
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case EmailNotVerified = 'EMAIL_NOT_VERIFIED';
    case TokenInvalid = 'TOKEN_INVALID';
    case TokenExpired = 'TOKEN_EXPIRED';
    case SigningRequestExpired = 'SIGNING_REQUEST_EXPIRED';
    case SigningRequestCancelled = 'SIGNING_REQUEST_CANCELLED';
    case SigningTokenInvalid = 'SIGNING_TOKEN_INVALID';
    case OtpInvalid = 'OTP_INVALID';
    case OtpExpired = 'OTP_EXPIRED';
    case OtpMaxAttempts = 'OTP_MAX_ATTEMPTS';
    case DocumentAccessDenied = 'DOCUMENT_ACCESS_DENIED';
    case TenantRequired = 'TENANT_REQUIRED';
    case NotImplemented = 'NOT_IMPLEMENTED';
    case PlanLimitExceeded = 'PLAN_LIMIT_EXCEEDED';
    case SubscriptionInactive = 'SUBSCRIPTION_INACTIVE';
    case SigningRequestDeclined = 'SIGNING_REQUEST_DECLINED';
    case InvalidState = 'INVALID_STATE';
    case NotReady = 'NOT_READY';
    case IdempotencyInProgress = 'IDEMPOTENCY_IN_PROGRESS';
    case IdempotencyKeyReused = 'IDEMPOTENCY_KEY_REUSED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
