<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

enum AuditEventType: string
{
    case DocumentCreated = 'DOCUMENT_CREATED';
    case DocumentUploaded = 'DOCUMENT_UPLOADED';
    case SigningRequestCreated = 'SIGNING_REQUEST_CREATED';
    case InvitationSent = 'INVITATION_SENT';
    case InvitationDelivered = 'INVITATION_DELIVERED';
    case InvitationDeliveryFailed = 'INVITATION_DELIVERY_FAILED';
    case RecipientUpdated = 'RECIPIENT_UPDATED';
    case SigningLinkOpened = 'SIGNING_LINK_OPENED';
    case OtpRequested = 'OTP_REQUESTED';
    case OtpVerified = 'OTP_VERIFIED';
    case OtpFailed = 'OTP_FAILED';
    case DocumentViewed = 'DOCUMENT_VIEWED';
    case DocumentAccessUrlGenerated = 'DOCUMENT_ACCESS_URL_GENERATED';
    case SignatureApplied = 'SIGNATURE_APPLIED';
    case SigningCompleted = 'SIGNING_COMPLETED';
    case DocumentDownloaded = 'DOCUMENT_DOWNLOADED';
    case SigningRequestDeclined = 'SIGNING_REQUEST_DECLINED';
    case SigningRequestExpired = 'SIGNING_REQUEST_EXPIRED';
    case SigningRequestCancelled = 'SIGNING_REQUEST_CANCELLED';
    case DocumentVoided = 'DOCUMENT_VOIDED';
    case AdminAccessedDocument = 'ADMIN_ACCESSED_DOCUMENT';
    case AdminAccessedAuditTrail = 'ADMIN_ACCESSED_AUDIT_TRAIL';
    case OauthClientCreated = 'OAUTH_CLIENT_CREATED';
    case OauthClientUpdated = 'OAUTH_CLIENT_UPDATED';
    case OauthClientScopesChanged = 'OAUTH_CLIENT_SCOPES_CHANGED';
    case OauthClientKeyAdded = 'OAUTH_CLIENT_KEY_ADDED';
    case OauthClientKeyRevoked = 'OAUTH_CLIENT_KEY_REVOKED';
    case OauthClientDisabled = 'OAUTH_CLIENT_DISABLED';
    case OauthClientEnabled = 'OAUTH_CLIENT_ENABLED';
    case OauthClientRevoked = 'OAUTH_CLIENT_REVOKED';
    case OauthClientTokensRevoked = 'OAUTH_CLIENT_TOKENS_REVOKED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
