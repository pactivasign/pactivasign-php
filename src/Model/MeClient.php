<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class MeClient extends Model
{
    public readonly string $clientId;

    public readonly string $name;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->clientId = $this->string('clientId');
        $this->name = $this->string('name');
    }
}
