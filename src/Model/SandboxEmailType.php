<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

enum SandboxEmailType: string
{
    case SigningInvitation = 'SIGNING_INVITATION';
    case Otp = 'OTP';
    case Reminder = 'REMINDER';
    case Completion = 'COMPLETION';
    case CarbonCopy = 'CARBON_COPY';
    case Declined = 'DECLINED';
    case AccountVerification = 'ACCOUNT_VERIFICATION';
    case Voided = 'VOIDED';
    case Expired = 'EXPIRED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
