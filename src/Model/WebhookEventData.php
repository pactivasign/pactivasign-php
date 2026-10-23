<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class WebhookEventData extends Model
{
    public readonly string $envelopeId;

    /**
     * Envelope version after this change. Ignore events older than what you have.
     */
    public readonly int|float $version;

    public readonly EnvelopeStatus $status;

    public readonly ?string $externalId;

    /**
     * @var array<string, string>
     */
    public readonly array $metadata;

    /**
     * On recipient.* events.
     */
    public readonly ?WebhookEventRecipient $recipient;

    /**
     * On recipient.delivered and recipient.delivery_failed.
     */
    public readonly ?WebhookEventDelivery $delivery;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->envelopeId = $this->string('envelopeId');
        $this->version = $this->number('version');
        $this->status = $this->enum('status', EnvelopeStatus::class);
        $this->externalId = $this->has('externalId') ? $this->string('externalId') : null;
        $this->metadata = $this->stringMap('metadata');
        $this->recipient = $this->has('recipient') ? $this->model('recipient', WebhookEventRecipient::class) : null;
        $this->delivery = $this->has('delivery') ? $this->model('delivery', WebhookEventDelivery::class) : null;
    }
}
