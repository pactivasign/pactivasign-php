<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class MeRateLimits extends Model
{
    public readonly int|float $requestsPerMinute;

    /**
     * Uploads, conversions and sends.
     */
    public readonly int|float $expensivePerMinute;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->requestsPerMinute = $this->number('requestsPerMinute');
        $this->expensivePerMinute = $this->number('expensivePerMinute');
    }
}
