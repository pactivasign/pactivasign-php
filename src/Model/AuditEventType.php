<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

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
    case SigningRequestCorrected = 'SIGNING_REQUEST_CORRECTED';
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
    case ReminderSent = 'REMINDER_SENT';
    case WorkspaceSettingsUpdated = 'WORKSPACE_SETTINGS_UPDATED';
    case OrganizationSettingsUpdated = 'ORGANIZATION_SETTINGS_UPDATED';
    case SubscriptionChanged = 'SUBSCRIPTION_CHANGED';
    case WorkspaceStatusChanged = 'WORKSPACE_STATUS_CHANGED';
    case OrganizationStatusChanged = 'ORGANIZATION_STATUS_CHANGED';
    case LicenseIssued = 'LICENSE_ISSUED';
    case LicenseUpdated = 'LICENSE_UPDATED';
    case LicenseSuspended = 'LICENSE_SUSPENDED';
    case LicenseReactivated = 'LICENSE_REACTIVATED';
    case LicenseRevoked = 'LICENSE_REVOKED';
    case LicenseExpired = 'LICENSE_EXPIRED';
    case CompanyUpdated = 'COMPANY_UPDATED';
    case CompanyMemberRoleChanged = 'COMPANY_MEMBER_ROLE_CHANGED';
    case CompanyOwnershipTransferred = 'COMPANY_OWNERSHIP_TRANSFERRED';
    case AccountCreated = 'ACCOUNT_CREATED';
    case AccountUpdated = 'ACCOUNT_UPDATED';
    case AccountMemberRoleChanged = 'ACCOUNT_MEMBER_ROLE_CHANGED';
    case AccountMemberRemoved = 'ACCOUNT_MEMBER_REMOVED';
    case WorkspaceCreated = 'WORKSPACE_CREATED';
    case InviteSent = 'INVITE_SENT';
    case InviteAccepted = 'INVITE_ACCEPTED';
    case InviteRevoked = 'INVITE_REVOKED';
    case AccountMemberAdded = 'ACCOUNT_MEMBER_ADDED';
    case IntegrationWorkspaceGranted = 'INTEGRATION_WORKSPACE_GRANTED';
    case IntegrationWorkspaceRemoved = 'INTEGRATION_WORKSPACE_REMOVED';
    case CompanyStatusChanged = 'COMPANY_STATUS_CHANGED';
    case AccountStatusChanged = 'ACCOUNT_STATUS_CHANGED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
