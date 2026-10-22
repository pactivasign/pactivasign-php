<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class SigningRequest extends Model
{
    public readonly string $id;

    public readonly string $documentId;

    public readonly string $organizationId;

    public readonly SigningRequestStatus $status;

    public readonly bool $sequential;

    public readonly ?\DateTimeImmutable $expiresAt;

    public readonly \DateTimeImmutable $createdAt;

    public readonly \DateTimeImmutable $updatedAt;

    /**
     * @var list<SigningRequestSigner>
     */
    public readonly array $signers;

    /**
     * @var list<SigningRequestCcRecipient>
     */
    public readonly array $ccRecipients;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->documentId = $this->string('documentId');
        $this->organizationId = $this->string('organizationId');
        $this->status = $this->enum('status', SigningRequestStatus::class);
        $this->sequential = $this->bool('sequential');
        $this->expiresAt = $this->has('expiresAt') ? $this->date('expiresAt') : null;
        $this->createdAt = $this->date('createdAt');
        $this->updatedAt = $this->date('updatedAt');
        $this->signers = $this->models('signers', SigningRequestSigner::class);
        $this->ccRecipients = $this->models('ccRecipients', SigningRequestCcRecipient::class);
    }
}
