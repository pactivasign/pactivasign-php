<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class SigningCorrection extends Model
{
    /**
     * The document signers sign. For an envelope, its combined packet.
     */
    public readonly string $documentId;

    public readonly ?string $envelopeId;

    public readonly string $signingRequestId;

    public readonly bool $sequential;

    /**
     * Pages in the document. Fields must be on pages 1 to pageCount.
     */
    public readonly int|float $pageCount;

    /**
     * False once a signer has signed, or the document is no longer waiting for signatures.
     */
    public readonly bool $correctable;

    /**
     * Why it cannot be corrected, when correctable is false.
     */
    public readonly ?string $reason;

    /**
     * @var list<CorrectionRecipient>
     */
    public readonly array $recipients;

    /**
     * @var list<CorrectionField>
     */
    public readonly array $fields;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->documentId = $this->string('documentId');
        $this->envelopeId = $this->has('envelopeId') ? $this->string('envelopeId') : null;
        $this->signingRequestId = $this->string('signingRequestId');
        $this->sequential = $this->bool('sequential');
        $this->pageCount = $this->number('pageCount');
        $this->correctable = $this->bool('correctable');
        $this->reason = $this->has('reason') ? $this->string('reason') : null;
        $this->recipients = $this->models('recipients', CorrectionRecipient::class);
        $this->fields = $this->models('fields', CorrectionField::class);
    }
}
