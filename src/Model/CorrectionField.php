<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class CorrectionField extends Model
{
    public readonly string $id;

    /**
     * Recipient id, or null for any signer.
     */
    public readonly ?string $recipient;

    public readonly string $type;

    public readonly int|float $page;

    public readonly int|float $x;

    public readonly int|float $y;

    public readonly int|float $width;

    public readonly int|float $height;

    public readonly bool $required;

    /**
     * @var array<string, mixed>|null
     */
    public readonly ?array $metadata;

    /**
     * The signer has already filled or signed it. Moving it keeps the value; other changes clear it.
     */
    public readonly bool $filled;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->recipient = $this->has('recipient') ? $this->string('recipient') : null;
        $this->type = $this->string('type');
        $this->page = $this->number('page');
        $this->x = $this->number('x');
        $this->y = $this->number('y');
        $this->width = $this->number('width');
        $this->height = $this->number('height');
        $this->required = $this->bool('required');
        $this->metadata = $this->has('metadata') ? $this->object('metadata') : null;
        $this->filled = $this->bool('filled');
    }
}
