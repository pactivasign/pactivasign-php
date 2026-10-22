<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

enum DocumentStatus: string
{
    case Draft = 'DRAFT';
    case Preparing = 'PREPARING';
    case Sent = 'SENT';
    case InProgress = 'IN_PROGRESS';
    case Finalizing = 'FINALIZING';
    case Completed = 'COMPLETED';
    case Declined = 'DECLINED';
    case Expired = 'EXPIRED';
    case Voided = 'VOIDED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
