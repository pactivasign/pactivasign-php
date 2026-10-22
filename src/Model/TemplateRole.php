<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class TemplateRole extends Model
{
    public readonly string $id;

    public readonly string $templateId;

    /**
     * Unique within the template. Matched by roleName on POST /envelopes.
     */
    public readonly string $name;

    public readonly RecipientAction $action;

    public readonly int|float $signingOrder;

    public readonly int|float $position;

    public readonly \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->templateId = $this->string('templateId');
        $this->name = $this->string('name');
        $this->action = $this->enum('action', RecipientAction::class);
        $this->signingOrder = $this->number('signingOrder');
        $this->position = $this->number('position');
        $this->createdAt = $this->date('createdAt');
    }
}
