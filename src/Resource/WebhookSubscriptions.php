<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Resource;

use PactivaSign\Model\WebhookSubscription;
use PactivaSign\Model\WebhookSubscriptionWithSecret;
use PactivaSign\Model\WebhookTestQueued;

/**
 * Subscriptions and the delivery log for envelope events.
 *
 * @see \PactivaSign\Client
 */
final class WebhookSubscriptions extends ApiResource
{
    /**
     * List webhook subscriptions
     *
     * Scopes: webhooks:manage.
     *
     * `GET /api/v1/webhook-subscriptions` (webhookSubscriptions.list)
     *
     * @return list<WebhookSubscription>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function list(): array
    {
        return self::many($this->transport->send('GET', '/api/v1/webhook-subscriptions'), WebhookSubscription::class);
    }

    /**
     * Subscribe an HTTPS URL to envelope events. The signing secret is returned only here.
     *
     * Scopes: webhooks:manage.
     *
     * `POST /api/v1/webhook-subscriptions` (webhookSubscriptions.create)
     *
     * @param array{
     *     url: string,
     *     eventTypes: list<'envelope.sent'|'envelope.completed'|'envelope.declined'|'envelope.voided'|'envelope.expired'|'recipient.delivered'|'recipient.delivery_failed'|'recipient.viewed'|'recipient.signed'|'recipient.declined'|\PactivaSign\Model\WebhookEventType>,
     *     description?: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function create(array $params): WebhookSubscriptionWithSecret
    {
        return self::one($this->transport->send('POST', '/api/v1/webhook-subscriptions', [
            'json' => self::normalize($params),
        ]), WebhookSubscriptionWithSecret::class);
    }

    /**
     * Get a webhook subscription
     *
     * Scopes: webhooks:manage.
     *
     * `GET /api/v1/webhook-subscriptions/{id}` (webhookSubscriptions.get)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function get(string $id): WebhookSubscription
    {
        return self::one($this->transport->send('GET', self::path('/api/v1/webhook-subscriptions/%s', $id)), WebhookSubscription::class);
    }

    /**
     * Delete a webhook subscription and its delivery log.
     *
     * Scopes: webhooks:manage.
     *
     * `DELETE /api/v1/webhook-subscriptions/{id}` (webhookSubscriptions.delete)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function delete(string $id): void
    {
        $this->transport->send('DELETE', self::path('/api/v1/webhook-subscriptions/%s', $id));
    }

    /**
     * Change the URL or events, pause, or re-enable a disabled subscription
     *
     * Scopes: webhooks:manage.
     *
     * `PATCH /api/v1/webhook-subscriptions/{id}` (webhookSubscriptions.update)
     *
     * @param array{
     *     url?: string,
     *     eventTypes?: list<'envelope.sent'|'envelope.completed'|'envelope.declined'|'envelope.voided'|'envelope.expired'|'recipient.delivered'|'recipient.delivery_failed'|'recipient.viewed'|'recipient.signed'|'recipient.declined'|\PactivaSign\Model\WebhookEventType>,
     *     description?: string|null,
     *     active?: bool,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function update(string $id, array $params = []): WebhookSubscription
    {
        return self::one($this->transport->send('PATCH', self::path('/api/v1/webhook-subscriptions/%s', $id), [
            'json' => (object) self::normalize($params),
        ]), WebhookSubscription::class);
    }

    /**
     * New signing secret (returned once). The old one also signs for 24 hours, as a second v1.
     *
     * Scopes: webhooks:manage.
     *
     * `POST /api/v1/webhook-subscriptions/{id}/rotate-secret` (webhookSubscriptions.rotateSecret)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function rotateSecret(string $id): WebhookSubscriptionWithSecret
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/webhook-subscriptions/%s/rotate-secret', $id)), WebhookSubscriptionWithSecret::class);
    }

    /**
     * Send a webhook.test event to this subscription
     *
     * Scopes: webhooks:manage.
     *
     * `POST /api/v1/webhook-subscriptions/{id}/test` (webhookSubscriptions.sendTest)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function sendTest(string $id): WebhookTestQueued
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/webhook-subscriptions/%s/test', $id)), WebhookTestQueued::class);
    }
}
