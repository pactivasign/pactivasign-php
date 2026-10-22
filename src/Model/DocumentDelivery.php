<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class DocumentDelivery extends Model
{
    public readonly string $name;

    public readonly string $email;

    public readonly int|float $signingOrder;

    /**
     * Latest invitation email status (QUEUED, ACCEPTED, DELIVERED, FAILED); null before one is sent.
     */
    public readonly ?string $status;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->name = $this->string('name');
        $this->email = $this->string('email');
        $this->signingOrder = $this->number('signingOrder');
        $this->status = $this->has('status') ? $this->string('status') : null;
    }
}
