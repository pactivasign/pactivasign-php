<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class Envelope extends Model
{
    public readonly string $id;

    public readonly string $name;

    public readonly EnvelopeStatus $status;

    /**
     * Bumped on every change. Webhooks carry it; ignore events older than what you have.
     */
    public readonly int|float $version;

    public readonly ?string $externalId;

    /**
     * @var array<string, string>
     */
    public readonly array $metadata;

    public readonly bool $sequential;

    public readonly ?\DateTimeImmutable $expiresAt;

    public readonly \DateTimeImmutable $createdAt;

    public readonly \DateTimeImmutable $updatedAt;

    public readonly ?\DateTimeImmutable $completedAt;

    public readonly ?\DateTimeImmutable $declinedAt;

    public readonly ?\DateTimeImmutable $voidedAt;

    public readonly ?string $voidReason;

    /**
     * The combined document the recipients sign.
     */
    public readonly string $packetDocumentId;

    /**
     * @var list<EnvelopeDocument>
     */
    public readonly array $documents;

    /**
     * @var list<EnvelopeRecipient>
     */
    public readonly array $recipients;

    /**
     * @var list<EnvelopeCcRecipient>
     */
    public readonly array $ccRecipients;

    /**
     * Kept for existing callers. Use status and recipients.
     * @deprecated Kept for existing callers. Use status and recipients.
     */
    public readonly ?EnvelopeSigningRequestRef $signingRequest;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->name = $this->string('name');
        $this->status = $this->enum('status', EnvelopeStatus::class);
        $this->version = $this->number('version');
        $this->externalId = $this->has('externalId') ? $this->string('externalId') : null;
        $this->metadata = $this->stringMap('metadata');
        $this->sequential = $this->bool('sequential');
        $this->expiresAt = $this->has('expiresAt') ? $this->date('expiresAt') : null;
        $this->createdAt = $this->date('createdAt');
        $this->updatedAt = $this->date('updatedAt');
        $this->completedAt = $this->has('completedAt') ? $this->date('completedAt') : null;
        $this->declinedAt = $this->has('declinedAt') ? $this->date('declinedAt') : null;
        $this->voidedAt = $this->has('voidedAt') ? $this->date('voidedAt') : null;
        $this->voidReason = $this->has('voidReason') ? $this->string('voidReason') : null;
        $this->packetDocumentId = $this->string('packetDocumentId');
        $this->documents = $this->models('documents', EnvelopeDocument::class);
        $this->recipients = $this->models('recipients', EnvelopeRecipient::class);
        $this->ccRecipients = $this->models('ccRecipients', EnvelopeCcRecipient::class);
        $this->signingRequest = $this->has('signingRequest') ? $this->model('signingRequest', EnvelopeSigningRequestRef::class) : null;
    }
}
