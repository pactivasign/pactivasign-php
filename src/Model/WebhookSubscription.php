<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class WebhookSubscription extends Model
{
    public readonly string $id;

    public readonly string $url;

    public readonly ?string $description;

    /**
     * @var list<WebhookEventType>
     */
    public readonly array $eventTypes;

    /**
     * False when paused, or disabled after a 410 or 3 days of failures.
     */
    public readonly bool $active;

    public readonly ?\DateTimeImmutable $disabledAt;

    public readonly ?string $disabledReason;

    public readonly ?\DateTimeImmutable $failingSince;

    /**
     * Until then, deliveries also carry a signature made with the previous secret.
     */
    public readonly ?\DateTimeImmutable $previousSecretExpiresAt;

    public readonly \DateTimeImmutable $createdAt;

    public readonly \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->url = $this->string('url');
        $this->description = $this->has('description') ? $this->string('description') : null;
        $this->eventTypes = $this->enums('eventTypes', WebhookEventType::class);
        $this->active = $this->bool('active');
        $this->disabledAt = $this->has('disabledAt') ? $this->date('disabledAt') : null;
        $this->disabledReason = $this->has('disabledReason') ? $this->string('disabledReason') : null;
        $this->failingSince = $this->has('failingSince') ? $this->date('failingSince') : null;
        $this->previousSecretExpiresAt = $this->has('previousSecretExpiresAt') ? $this->date('previousSecretExpiresAt') : null;
        $this->createdAt = $this->date('createdAt');
        $this->updatedAt = $this->date('updatedAt');
    }
}
