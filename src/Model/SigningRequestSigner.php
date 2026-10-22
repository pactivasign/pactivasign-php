<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class SigningRequestSigner extends Model
{
    /**
     * Use as recipientId on resend.
     */
    public readonly string $id;

    public readonly string $signerId;

    public readonly string $email;

    public readonly string $name;

    public readonly int|float $signingOrder;

    public readonly SignerParticipantStatus $status;

    public readonly ?\DateTimeImmutable $signedAt;

    public readonly ?string $recipientRoleId;

    public readonly ?string $roleName;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->signerId = $this->string('signerId');
        $this->email = $this->string('email');
        $this->name = $this->string('name');
        $this->signingOrder = $this->number('signingOrder');
        $this->status = $this->enum('status', SignerParticipantStatus::class);
        $this->signedAt = $this->has('signedAt') ? $this->date('signedAt') : null;
        $this->recipientRoleId = $this->has('recipientRoleId') ? $this->string('recipientRoleId') : null;
        $this->roleName = $this->has('roleName') ? $this->string('roleName') : null;
    }
}
