<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class CorrectionRecipient extends Model
{
    public readonly string $id;

    public readonly string $role;

    public readonly ?string $customRoleId;

    /**
     * Custom or template role name.
     */
    public readonly ?string $roleName;

    public readonly string $email;

    public readonly string $name;

    public readonly int|float $signingOrder;

    /**
     * Signers only; null for CC.
     */
    public readonly ?string $status;

    /**
     * Whether they have been emailed a link. A new recipient is invited when the correction is saved.
     */
    public readonly bool $invited;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->role = $this->string('role');
        $this->customRoleId = $this->has('customRoleId') ? $this->string('customRoleId') : null;
        $this->roleName = $this->has('roleName') ? $this->string('roleName') : null;
        $this->email = $this->string('email');
        $this->name = $this->string('name');
        $this->signingOrder = $this->number('signingOrder');
        $this->status = $this->has('status') ? $this->string('status') : null;
        $this->invited = $this->bool('invited');
    }
}
