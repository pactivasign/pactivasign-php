<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class WebhookDelivery extends Model
{
    public readonly string $id;

    public readonly string $eventId;

    public readonly string $eventType;

    public readonly ?string $envelopeId;

    public readonly \DateTimeImmutable $occurredAt;

    public readonly string $subscriptionId;

    public readonly WebhookDeliveryStatus $status;

    public readonly int|float $attempts;

    public readonly ?\DateTimeImmutable $nextAttemptAt;

    public readonly ?\DateTimeImmutable $lastAttemptAt;

    public readonly int|float|null $lastStatusCode;

    public readonly ?string $lastError;

    public readonly int|float|null $lastDurationMs;

    /**
     * First bytes of the receiver response.
     */
    public readonly ?string $lastResponseSnippet;

    public readonly ?\DateTimeImmutable $deliveredAt;

    public readonly \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->eventId = $this->string('eventId');
        $this->eventType = $this->string('eventType');
        $this->envelopeId = $this->has('envelopeId') ? $this->string('envelopeId') : null;
        $this->occurredAt = $this->date('occurredAt');
        $this->subscriptionId = $this->string('subscriptionId');
        $this->status = $this->enum('status', WebhookDeliveryStatus::class);
        $this->attempts = $this->number('attempts');
        $this->nextAttemptAt = $this->has('nextAttemptAt') ? $this->date('nextAttemptAt') : null;
        $this->lastAttemptAt = $this->has('lastAttemptAt') ? $this->date('lastAttemptAt') : null;
        $this->lastStatusCode = $this->has('lastStatusCode') ? $this->number('lastStatusCode') : null;
        $this->lastError = $this->has('lastError') ? $this->string('lastError') : null;
        $this->lastDurationMs = $this->has('lastDurationMs') ? $this->number('lastDurationMs') : null;
        $this->lastResponseSnippet = $this->has('lastResponseSnippet') ? $this->string('lastResponseSnippet') : null;
        $this->deliveredAt = $this->has('deliveredAt') ? $this->date('deliveredAt') : null;
        $this->createdAt = $this->date('createdAt');
    }
}
