<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class EnvelopeSigningRequestRef extends Model
{
    public readonly string $id;

    public readonly SigningRequestStatus $status;

    public readonly bool $sequential;

    public readonly ?\DateTimeImmutable $expiresAt;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->status = $this->enum('status', SigningRequestStatus::class);
        $this->sequential = $this->bool('sequential');
        $this->expiresAt = $this->has('expiresAt') ? $this->date('expiresAt') : null;
    }
}
