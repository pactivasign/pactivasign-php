<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class AuditEvent extends Model
{
    public readonly string $id;

    public readonly string $organizationId;

    public readonly ?string $documentId;

    public readonly ?string $signingRequestId;

    public readonly ?string $signerId;

    public readonly AuditEventType $eventType;

    public readonly \DateTimeImmutable $occurredAt;

    public readonly ActorType $actorType;

    public readonly ?string $actorId;

    public readonly ?string $actorName;

    public readonly ?string $actorEmail;

    /**
     * One readable sentence, e.g. "Jane Customer signed the document".
     */
    public readonly string $summary;

    public readonly ?string $ipAddress;

    public readonly ?string $userAgent;

    /**
     * @var array<string, mixed>|null
     */
    public readonly ?array $metadata;

    /**
     * SHA-256 of this event chained to the previous one.
     */
    public readonly string $eventHash;

    public readonly string $previousEventHash;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->organizationId = $this->string('organizationId');
        $this->documentId = $this->has('documentId') ? $this->string('documentId') : null;
        $this->signingRequestId = $this->has('signingRequestId') ? $this->string('signingRequestId') : null;
        $this->signerId = $this->has('signerId') ? $this->string('signerId') : null;
        $this->eventType = $this->enum('eventType', AuditEventType::class);
        $this->occurredAt = $this->date('occurredAt');
        $this->actorType = $this->enum('actorType', ActorType::class);
        $this->actorId = $this->has('actorId') ? $this->string('actorId') : null;
        $this->actorName = $this->has('actorName') ? $this->string('actorName') : null;
        $this->actorEmail = $this->has('actorEmail') ? $this->string('actorEmail') : null;
        $this->summary = $this->string('summary');
        $this->ipAddress = $this->has('ipAddress') ? $this->string('ipAddress') : null;
        $this->userAgent = $this->has('userAgent') ? $this->string('userAgent') : null;
        $this->metadata = $this->has('metadata') ? $this->object('metadata') : null;
        $this->eventHash = $this->string('eventHash');
        $this->previousEventHash = $this->string('previousEventHash');
    }
}
