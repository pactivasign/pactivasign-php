<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Resource;

use PactivaSign\Model\WebhookDelivery;
use PactivaSign\Model\WebhookRedeliveryQueued;
use PactivaSign\Pagination\Paginator;

/**
 * Subscriptions and the delivery log for envelope events.
 *
 * @see \PactivaSign\Client
 */
final class WebhookDeliveries extends ApiResource
{
    /**
     * Delivery log, newest first
     *
     * Scopes: webhooks:manage.
     *
     * `GET /api/v1/webhook-deliveries` (webhookDeliveries.list)
     *
     * @param array{
     *     limit?: int|float,
     *     page?: int|float,
     *     subscriptionId?: string,
     *     status?: 'PENDING'|'SUCCEEDED'|'FAILED'|\PactivaSign\Model\WebhookDeliveryStatus,
     *     eventId?: string,
     * } $query
     * @return Paginator<WebhookDelivery>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function list(array $query = []): Paginator
    {
        $query = self::query($query);

        return new Paginator(fn (?string $cursor) => self::page(
            $this->transport->send('GET', '/api/v1/webhook-deliveries', ['query' => $cursor === null ? $query : ['cursor' => $cursor] + $query]),
            WebhookDelivery::class,
        ));
    }

    /**
     * Send a delivered or failed event again (same event id and body)
     *
     * Scopes: webhooks:manage.
     *
     * `POST /api/v1/webhook-deliveries/{id}/redeliver` (webhookDeliveries.redeliver)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function redeliver(string $id): WebhookRedeliveryQueued
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/webhook-deliveries/%s/redeliver', $id)), WebhookRedeliveryQueued::class);
    }
}
