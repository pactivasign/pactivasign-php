<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class WebhookRedeliveryQueued extends Model
{
    /**
     * The delivery, queued again with the same event.
     */
    public readonly string $id;

    public readonly string $status;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->status = $this->string('status');
    }
}
