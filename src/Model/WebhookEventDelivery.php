<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class WebhookEventDelivery extends Model
{
    /**
     * Azure Communication Services delivery status, e.g. Delivered, Failed, Bounced, Suppressed.
     */
    public readonly string $status;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->status = $this->string('status');
    }
}
