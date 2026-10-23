<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class DocumentFromTemplate extends Model
{
    public readonly Document $document;

    /**
     * @var list<DocumentField>
     */
    public readonly array $fields;

    public readonly ?FileAccess $file;

    /**
     * One empty slot per template role. Fill name and email with PUT /documents/{id}/recipients.
     * @var list<DocumentRecipient>
     */
    public readonly array $recipients;

    /**
     * True when the roles have different signing orders; send with sequential.
     */
    public readonly bool $suggestedSequential;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->document = $this->model('document', Document::class);
        $this->fields = $this->models('fields', DocumentField::class);
        $this->file = $this->has('file') ? $this->model('file', FileAccess::class) : null;
        $this->recipients = $this->models('recipients', DocumentRecipient::class);
        $this->suggestedSequential = $this->bool('suggestedSequential');
    }
}
