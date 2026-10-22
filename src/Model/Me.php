<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Model;

final class Me extends Model
{
    public readonly string $authKind;

    /**
     * Which PactivaSign environment answered.
     */
    public readonly string $environment;

    public readonly MeOrganization $organization;

    /**
     * The calling OAuth client; null otherwise.
     */
    public readonly ?MeClient $client;

    /**
     * Scopes this token can use right now.
     * @var list<string>
     */
    public readonly array $scopes;

    public readonly MePlan $plan;

    /**
     * Per-client limits; null for non-OAuth callers.
     */
    public readonly ?MeRateLimits $rateLimits;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        parent::__construct($data);

        $this->authKind = $this->string('authKind');
        $this->environment = $this->string('environment');
        $this->organization = $this->model('organization', MeOrganization::class);
        $this->client = $this->has('client') ? $this->model('client', MeClient::class) : null;
        $this->scopes = $this->strings('scopes');
        $this->plan = $this->model('plan', MePlan::class);
        $this->rateLimits = $this->has('rateLimits') ? $this->model('rateLimits', MeRateLimits::class) : null;
    }
}
