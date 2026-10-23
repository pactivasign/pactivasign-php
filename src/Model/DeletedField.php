<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class DeletedField extends Model
{
    public readonly bool $deleted;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->deleted = $this->bool('deleted');
    }
}
