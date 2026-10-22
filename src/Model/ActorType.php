<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

enum ActorType: string
{
    case User = 'USER';
    case Signer = 'SIGNER';
    case System = 'SYSTEM';
    case Admin = 'ADMIN';
    case CcRecipient = 'CC_RECIPIENT';
    case OauthClient = 'OAUTH_CLIENT';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
