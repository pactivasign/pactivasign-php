<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Resource;

use PactivaSign\Model\SigningRequest;

/**
 * Send a single document for signature and resend invitations.
 *
 * @see \PactivaSign\Client
 */
final class SigningRequests extends ApiResource
{
    /**
     * Get the latest signing request for a document (signers + CC list)
     *
     * Scopes: signing:read.
     *
     * `GET /api/v1/documents/{id}/signing-request` (signingRequests.getLatest)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function getLatest(string $id): SigningRequest
    {
        return self::one($this->transport->send('GET', self::path('/api/v1/documents/%s/signing-request', $id)), SigningRequest::class);
    }

    /**
     * Email a new signing link to recipients waiting to sign, or to one of them. Earlier links keep working. Limited to one resend per recipient every 10 minutes and 10 invitations a day; 429 with Retry-After when every target is limited. Documents sent inside an envelope are resent with POST /envelopes/:id/resend.
     *
     * Scopes: signing:manage.
     *
     * `POST /api/v1/documents/{id}/signing-request/resend` (signingRequests.resend)
     *
     * @param array{
     *     recipientId?: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function resend(string $id, array $params = []): SigningRequest
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/signing-request/resend', $id), [
            'json' => (object) self::normalize($params),
        ]), SigningRequest::class);
    }

    /**
     * Create a signing request with one or more signers and optional CC recipients
     *
     * Scopes: signing:create.
     *
     * `POST /api/v1/documents/{id}/signing-requests` (signingRequests.create)
     *
     * @param array{
     *     sequential: bool,
     *     expiresAt?: string,
     *     signers?: list<array{
     *         email: string,
     *         name: string,
     *         signingOrder: int|float,
     *         customRoleId?: string,
     *         templateRoleId?: string,
     *     }>,
     *     ccRecipients?: list<array{
     *         email: string,
     *         name: string,
     *         customRoleId?: string,
     *         templateRoleId?: string,
     *     }>,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function create(string $id, array $params): SigningRequest
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/signing-requests', $id), [
            'json' => self::normalize($params),
        ]), SigningRequest::class);
    }
}
