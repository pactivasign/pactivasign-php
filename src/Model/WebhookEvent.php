<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class WebhookEvent extends Model
{
    public readonly string $id;

    public readonly WebhookEventType $type;

    public readonly \DateTimeImmutable $occurredAt;

    public readonly string $organizationId;

    public readonly WebhookEventData $data;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->type = $this->enum('type', WebhookEventType::class);
        $this->occurredAt = $this->date('occurredAt');
        $this->organizationId = $this->string('organizationId');
        $this->data = $this->model('data', WebhookEventData::class);
    }
}
