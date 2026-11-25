<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class WebhookEvent extends Model
{
    public readonly string $id;

    public readonly WebhookEventType $type;

    public readonly \DateTimeImmutable $occurredAt;

    /**
     * The workspace the envelope belongs to.
     */
    public readonly string $workspaceId;

    /**
     * The account that owns the workspace. Integrations covering several workspaces can route on workspaceId.
     */
    public readonly ?string $accountId;

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
        $this->workspaceId = $this->string('workspaceId');
        $this->accountId = $this->has('accountId') ? $this->string('accountId') : null;
        $this->data = $this->model('data', WebhookEventData::class);
    }
}
