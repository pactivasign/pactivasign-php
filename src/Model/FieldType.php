<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

enum FieldType: string
{
    case Signature = 'SIGNATURE';
    case Initial = 'INITIAL';
    case Name = 'NAME';
    case Email = 'EMAIL';
    case Date = 'DATE';
    case Text = 'TEXT';
    case Checkbox = 'CHECKBOX';
    case Radio = 'RADIO';
    case Dropdown = 'DROPDOWN';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
