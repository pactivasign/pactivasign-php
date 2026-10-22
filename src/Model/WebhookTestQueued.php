<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class WebhookTestQueued extends Model
{
    public readonly string $eventId;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->eventId = $this->string('eventId');
    }
}
