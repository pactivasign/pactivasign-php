<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class DocumentField extends Model
{
    public readonly string $id;

    public readonly string $documentId;

    public readonly string $documentVersionId;

    public readonly ?string $signerId;

    public readonly FieldType $type;

    /**
     * 1-based page.
     */
    public readonly int|float $page;

    /**
     * PDF points from the left.
     */
    public readonly int|float $x;

    /**
     * PDF points from the top.
     */
    public readonly int|float $y;

    public readonly int|float $width;

    public readonly int|float $height;

    public readonly bool $required;

    /**
     * Filled or default value; shape depends on type.
     * @var array<string, mixed>|null
     */
    public readonly ?array $value;

    /**
     * recipientEmail, recipientRole, font and other layout hints.
     * @var array<string, mixed>|null
     */
    public readonly ?array $metadata;

    public readonly ?string $templateRoleId;

    public readonly \DateTimeImmutable $createdAt;

    public readonly \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->documentId = $this->string('documentId');
        $this->documentVersionId = $this->string('documentVersionId');
        $this->signerId = $this->has('signerId') ? $this->string('signerId') : null;
        $this->type = $this->enum('type', FieldType::class);
        $this->page = $this->number('page');
        $this->x = $this->number('x');
        $this->y = $this->number('y');
        $this->width = $this->number('width');
        $this->height = $this->number('height');
        $this->required = $this->bool('required');
        $this->value = $this->has('value') ? $this->object('value') : null;
        $this->metadata = $this->has('metadata') ? $this->object('metadata') : null;
        $this->templateRoleId = $this->has('templateRoleId') ? $this->string('templateRoleId') : null;
        $this->createdAt = $this->date('createdAt');
        $this->updatedAt = $this->date('updatedAt');
    }
}
