<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class Template extends Model
{
    public readonly string $id;

    public readonly string $organizationId;

    public readonly string $name;

    public readonly TemplateStatus $status;

    public readonly ?string $contentHash;

    public readonly ?string $sourceFilename;

    public readonly \DateTimeImmutable $createdAt;

    public readonly \DateTimeImmutable $updatedAt;

    /**
     * @var list<TemplateField>
     */
    public readonly array $fields;

    /**
     * @var list<TemplateRole>
     */
    public readonly array $roles;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->organizationId = $this->string('organizationId');
        $this->name = $this->string('name');
        $this->status = $this->enum('status', TemplateStatus::class);
        $this->contentHash = $this->has('contentHash') ? $this->string('contentHash') : null;
        $this->sourceFilename = $this->has('sourceFilename') ? $this->string('sourceFilename') : null;
        $this->createdAt = $this->date('createdAt');
        $this->updatedAt = $this->date('updatedAt');
        $this->fields = $this->models('fields', TemplateField::class);
        $this->roles = $this->models('roles', TemplateRole::class);
    }
}
