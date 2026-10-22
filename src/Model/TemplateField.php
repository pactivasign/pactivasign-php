<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class TemplateField extends Model
{
    public readonly string $id;

    public readonly string $templateId;

    public readonly FieldType $type;

    public readonly int|float $page;

    public readonly int|float $x;

    public readonly int|float $y;

    public readonly int|float $width;

    public readonly int|float $height;

    public readonly bool $required;

    public readonly ?string $templateRoleId;

    /**
     * fieldIdentifier (prefill key on POST /envelopes values), font and layout hints.
     * @var array<string, mixed>|null
     */
    public readonly ?array $metadata;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->templateId = $this->string('templateId');
        $this->type = $this->enum('type', FieldType::class);
        $this->page = $this->number('page');
        $this->x = $this->number('x');
        $this->y = $this->number('y');
        $this->width = $this->number('width');
        $this->height = $this->number('height');
        $this->required = $this->bool('required');
        $this->templateRoleId = $this->has('templateRoleId') ? $this->string('templateRoleId') : null;
        $this->metadata = $this->has('metadata') ? $this->object('metadata') : null;
    }
}
