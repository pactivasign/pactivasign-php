<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

enum OrganizationStatus: string
{
    case Active = 'ACTIVE';
    case Suspended = 'SUSPENDED';
    case Deleted = 'DELETED';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
