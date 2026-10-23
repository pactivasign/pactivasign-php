<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

/**
 * CREATED means not invited yet (a later signer in a sequential envelope).
 */
enum RecipientStatus: string
{
    case Created = 'CREATED';
    case Sent = 'SENT';
    case Viewed = 'VIEWED';
    case Signed = 'SIGNED';
    case Declined = 'DECLINED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
