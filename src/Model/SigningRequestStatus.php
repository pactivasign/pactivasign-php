<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

enum SigningRequestStatus: string
{
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case Opened = 'OPENED';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Declined = 'DECLINED';
    case Expired = 'EXPIRED';
    case Cancelled = 'CANCELLED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
