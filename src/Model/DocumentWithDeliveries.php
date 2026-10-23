<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class DocumentWithDeliveries extends Model
{
    public readonly string $id;

    public readonly string $organizationId;

    public readonly string $name;

    public readonly DocumentStatus $status;

    /**
     * User who created it; null when an OAuth client did.
     */
    public readonly ?string $createdById;

    public readonly ?string $createdByOAuthClientId;

    /**
     * SHA-256 of the original PDF.
     */
    public readonly ?string $originalHash;

    /**
     * SHA-256 of the signed PDF.
     */
    public readonly ?string $finalHash;

    public readonly \DateTimeImmutable $createdAt;

    public readonly \DateTimeImmutable $updatedAt;

    public readonly ?\DateTimeImmutable $completedAt;

    /**
     * One per signer on the latest signing request.
     * @var list<DocumentDelivery>
     */
    public readonly array $deliveries;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->organizationId = $this->string('organizationId');
        $this->name = $this->string('name');
        $this->status = $this->enum('status', DocumentStatus::class);
        $this->createdById = $this->has('createdById') ? $this->string('createdById') : null;
        $this->createdByOAuthClientId = $this->has('createdByOAuthClientId') ? $this->string('createdByOAuthClientId') : null;
        $this->originalHash = $this->has('originalHash') ? $this->string('originalHash') : null;
        $this->finalHash = $this->has('finalHash') ? $this->string('finalHash') : null;
        $this->createdAt = $this->date('createdAt');
        $this->updatedAt = $this->date('updatedAt');
        $this->completedAt = $this->has('completedAt') ? $this->date('completedAt') : null;
        $this->deliveries = $this->models('deliveries', DocumentDelivery::class);
    }
}
