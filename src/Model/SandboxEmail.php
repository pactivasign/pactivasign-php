<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Model;

final class SandboxEmail extends Model
{
    public readonly string $id;

    public readonly SandboxEmailType $type;

    public readonly string $to;

    public readonly string $subject;

    /**
     * The plain-text body as it would have been sent.
     */
    public readonly string $text;

    /**
     * The link the email asks the recipient to open: the signing link of an invitation, the copy link of a CC email.
     */
    public readonly ?string $actionUrl;

    /**
     * The one-time code of an OTP email.
     */
    public readonly ?string $code;

    public readonly ?string $signingRequestId;

    public readonly \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->id = $this->string('id');
        $this->type = $this->enum('type', SandboxEmailType::class);
        $this->to = $this->string('to');
        $this->subject = $this->string('subject');
        $this->text = $this->string('text');
        $this->actionUrl = $this->has('actionUrl') ? $this->string('actionUrl') : null;
        $this->code = $this->has('code') ? $this->string('code') : null;
        $this->signingRequestId = $this->has('signingRequestId') ? $this->string('signingRequestId') : null;
        $this->createdAt = $this->date('createdAt');
    }
}
