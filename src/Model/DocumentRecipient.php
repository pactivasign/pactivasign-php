<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class DocumentRecipient extends Model
{
    public readonly string $id;

    /**
     * Null in an unfilled template slot.
     */
    public readonly ?string $email;

    public readonly ?string $name;

    public readonly DocumentRecipientRole $role;

    public readonly RecipientAction $action;

    public readonly int|float $signingOrder;

    public readonly ?RecipientCustomRole $customRole;

    public readonly ?RecipientTemplateRole $templateRole;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->email = $this->has('email') ? $this->string('email') : null;
        $this->name = $this->has('name') ? $this->string('name') : null;
        $this->role = $this->enum('role', DocumentRecipientRole::class);
        $this->action = $this->enum('action', RecipientAction::class);
        $this->signingOrder = $this->number('signingOrder');
        $this->customRole = $this->has('customRole') ? $this->model('customRole', RecipientCustomRole::class) : null;
        $this->templateRole = $this->has('templateRole') ? $this->model('templateRole', RecipientTemplateRole::class) : null;
    }
}
