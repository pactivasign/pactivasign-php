<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class MeApprovedWorkspace extends Model
{
    /**
     * Send as X-Workspace-Id to work in this workspace.
     */
    public readonly string $id;

    public readonly string $name;

    /**
     * The integration's scopes in this workspace.
     * @var list<string>
     */
    public readonly array $scopes;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->name = $this->string('name');
        $this->scopes = $this->strings('scopes');
    }
}
