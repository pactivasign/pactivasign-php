<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class DocumentEnvelope extends Model
{
    /**
     * The envelope ID returned by POST /envelopes.
     */
    public readonly string $id;

    public readonly string $name;

    public readonly DocumentStatus $status;

    /**
     * The integrator's own reference, if one was sent.
     */
    public readonly ?string $externalId;

    /**
     * This document's place in the envelope, from 0.
     */
    public readonly int|float $position;

    /**
     * How many documents the envelope holds.
     */
    public readonly int|float $documentCount;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->name = $this->string('name');
        $this->status = $this->enum('status', DocumentStatus::class);
        $this->externalId = $this->has('externalId') ? $this->string('externalId') : null;
        $this->position = $this->number('position');
        $this->documentCount = $this->number('documentCount');
    }
}
