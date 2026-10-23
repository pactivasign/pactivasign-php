<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

enum WebhookEventType: string
{
    case EnvelopeSent = 'envelope.sent';
    case EnvelopeCompleted = 'envelope.completed';
    case EnvelopeDeclined = 'envelope.declined';
    case EnvelopeVoided = 'envelope.voided';
    case EnvelopeExpired = 'envelope.expired';
    case RecipientDelivered = 'recipient.delivered';
    case RecipientDeliveryFailed = 'recipient.delivery_failed';
    case RecipientViewed = 'recipient.viewed';
    case RecipientSigned = 'recipient.signed';
    case RecipientDeclined = 'recipient.declined';

    /** A value added to the API after this SDK version. The raw value is in the model's toArray(). */
    case Unknown = 'unknown';
}
