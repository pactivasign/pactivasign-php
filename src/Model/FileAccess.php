<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class FileAccess extends Model
{
    public readonly string $contentUrl;

    public readonly string $contentType;

    public readonly string $variant;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->contentUrl = $this->string('contentUrl');
        $this->contentType = $this->string('contentType');
        $this->variant = $this->string('variant');
    }
}
