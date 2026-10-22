<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Resource;

use PactivaSign\Http\Transport;

/**
 * The API resources, as properties of the Client: `$pactiva->envelopes->create(...)`.
 */
trait ClientResources
{
    public readonly Documents $documents;
    public readonly Envelopes $envelopes;
    public readonly Me $me;
    public readonly SigningRequests $signingRequests;
    public readonly Templates $templates;
    public readonly WebhookDeliveries $webhookDeliveries;
    public readonly WebhookSubscriptions $webhookSubscriptions;

    private function initializeResources(Transport $transport): void
    {
        $this->documents = new Documents($transport);
        $this->envelopes = new Envelopes($transport);
        $this->me = new Me($transport);
        $this->signingRequests = new SigningRequests($transport);
        $this->templates = new Templates($transport);
        $this->webhookDeliveries = new WebhookDeliveries($transport);
        $this->webhookSubscriptions = new WebhookSubscriptions($transport);
    }
}
