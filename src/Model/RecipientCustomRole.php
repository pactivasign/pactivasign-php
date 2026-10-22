<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class RecipientCustomRole extends Model
{
    public readonly string $id;

    public readonly string $name;

    public readonly RecipientAction $action;

    public readonly bool $deleted;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->name = $this->string('name');
        $this->action = $this->enum('action', RecipientAction::class);
        $this->deleted = $this->bool('deleted');
    }
}
