<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class EnvelopeCcRecipient extends Model
{
    public readonly string $id;

    public readonly string $email;

    public readonly string $name;

    public readonly ?string $roleName;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->email = $this->string('email');
        $this->name = $this->string('name');
        $this->roleName = $this->has('roleName') ? $this->string('roleName') : null;
    }
}
