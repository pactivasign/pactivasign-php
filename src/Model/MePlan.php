<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class MePlan extends Model
{
    public readonly PlanCode $code;

    public readonly string $name;

    public readonly SubscriptionStatus $subscriptionStatus;

    public readonly int|float $documentsPerMonth;

    public readonly int|float $documentsThisPeriod;

    public readonly int|float $signersPerRequest;

    public readonly \DateTimeImmutable $periodStart;

    public readonly \DateTimeImmutable $periodEnd;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->code = $this->enum('code', PlanCode::class);
        $this->name = $this->string('name');
        $this->subscriptionStatus = $this->enum('subscriptionStatus', SubscriptionStatus::class);
        $this->documentsPerMonth = $this->number('documentsPerMonth');
        $this->documentsThisPeriod = $this->number('documentsThisPeriod');
        $this->signersPerRequest = $this->number('signersPerRequest');
        $this->periodStart = $this->date('periodStart');
        $this->periodEnd = $this->date('periodEnd');
    }
}
