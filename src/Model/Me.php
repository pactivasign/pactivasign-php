<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Model;

final class Me extends Model
{
    public readonly string $authKind;

    /**
     * Which PactivaSign environment answered.
     */
    public readonly string $environment;

    /**
     * The workspace this request ran in.
     */
    public readonly MeWorkspace $workspace;

    /**
     * The account that owns the workspace and the integration.
     */
    public readonly ?string $accountId;

    /**
     * For an API key or OAuth client: every workspace it may use. With more than one, send X-Workspace-Id on each request. Empty for dashboard users.
     * @var list<MeApprovedWorkspace>
     */
    public readonly array $workspaces;

    /**
     * The calling OAuth client; null otherwise.
     */
    public readonly ?MeClient $client;

    /**
     * Scopes this token can use right now, in this workspace.
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
        $this->workspace = $this->model('workspace', MeWorkspace::class);
        $this->accountId = $this->has('accountId') ? $this->string('accountId') : null;
        $this->workspaces = $this->models('workspaces', MeApprovedWorkspace::class);
        $this->client = $this->has('client') ? $this->model('client', MeClient::class) : null;
        $this->scopes = $this->strings('scopes');
        $this->plan = $this->model('plan', MePlan::class);
        $this->rateLimits = $this->has('rateLimits') ? $this->model('rateLimits', MeRateLimits::class) : null;
    }
}
