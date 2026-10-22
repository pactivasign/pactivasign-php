<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class EnvelopeDocument extends Model
{
    public readonly string $id;

    public readonly string $name;

    /**
     * 0-based place in the combined PDF.
     */
    public readonly int|float $position;

    public readonly int|float $pageCount;

    public readonly DocumentStatus $status;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->name = $this->string('name');
        $this->position = $this->number('position');
        $this->pageCount = $this->number('pageCount');
        $this->status = $this->enum('status', DocumentStatus::class);
    }
}
