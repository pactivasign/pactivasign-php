<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class EnvelopeRecipient extends Model
{
    /**
     * Use as recipientId on resend and PATCH.
     */
    public readonly string $id;

    public readonly string $email;

    public readonly string $name;

    public readonly ?string $roleName;

    /**
     * Order in a sequential envelope; 1 signs first.
     */
    public readonly int|float $signingOrder;

    /**
     * CREATED means not invited yet (a later signer in a sequential envelope).
     */
    public readonly RecipientStatus $status;

    public readonly ?\DateTimeImmutable $sentAt;

    public readonly ?\DateTimeImmutable $viewedAt;

    public readonly ?\DateTimeImmutable $signedAt;

    public readonly ?\DateTimeImmutable $declinedAt;

    public readonly ?string $declineReason;

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
        $this->signingOrder = $this->number('signingOrder');
        $this->status = $this->enum('status', RecipientStatus::class);
        $this->sentAt = $this->has('sentAt') ? $this->date('sentAt') : null;
        $this->viewedAt = $this->has('viewedAt') ? $this->date('viewedAt') : null;
        $this->signedAt = $this->has('signedAt') ? $this->date('signedAt') : null;
        $this->declinedAt = $this->has('declinedAt') ? $this->date('declinedAt') : null;
        $this->declineReason = $this->has('declineReason') ? $this->string('declineReason') : null;
    }
}
