<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class WebhookEventRecipient extends Model
{
    public readonly string $id;

    public readonly RecipientStatus $status;

    public readonly int|float $version;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->status = $this->enum('status', RecipientStatus::class);
        $this->version = $this->number('version');
    }
}
