<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Resource;

use PactivaSign\Auth\Jws;
use PactivaSign\Http\Download;
use PactivaSign\Model\Envelope;
use PactivaSign\Pagination\Paginator;

/**
 * Send one or more documents for signature and follow them to completion.
 *
 * @see \PactivaSign\Client
 */
final class Envelopes extends ApiResource
{
    /**
     * List envelopes, newest first. Pass nextCursor back as cursor for the next page.
     *
     * Scopes: signing:read.
     *
     * `GET /api/v1/envelopes` (envelopes.list)
     *
     * @param array{
     *     limit?: int|float,
     *     page?: int|float,
     *     status?: 'DRAFT'|'SENT'|'IN_PROGRESS'|'FINALIZING'|'COMPLETED'|'DECLINED'|'VOIDED'|'EXPIRED'|\PactivaSign\Model\EnvelopeStatus,
     *     externalId?: string,
     *     createdFrom?: string|\DateTimeInterface,
     *     createdTo?: string|\DateTimeInterface,
     * } $query
     * @return Paginator<Envelope>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function list(array $query = []): Paginator
    {
        $query = self::query($query);

        return new Paginator(fn (?string $cursor) => self::page(
            $this->transport->send('GET', '/api/v1/envelopes', ['query' => $cursor === null ? $query : ['cursor' => $cursor] + $query]),
            Envelope::class,
        ));
    }

    /**
     * Send one or more documents as an envelope. Each part can be a document id, a base64 PDF with fields, or a template.
     *
     * Scopes: signing:create, documents:create.
     *
     * `POST /api/v1/envelopes` (envelopes.create)
     *
     * @param array{
     *     name: string,
     *     documentIds?: list<string>,
     *     documents?: list<array{
     *         documentId?: string,
     *         name?: string,
     *         fileBase64?: string,
     *         fields?: list<array{
     *             type: 'SIGNATURE'|'INITIAL'|'NAME'|'EMAIL'|'DATE'|'TEXT'|'CHECKBOX'|'RADIO'|'DROPDOWN'|\PactivaSign\Model\FieldType,
     *             page: int|float,
     *             x: int|float,
     *             y: int|float,
     *             width?: int|float,
     *             height?: int|float,
     *             roleName?: string,
     *             recipientEmail?: string,
     *             defaultValue?: string|bool,
     *         }>,
     *         templateId?: string,
     *         values?: list<array{
     *             fieldIdentifier?: string,
     *             tabLabel?: string,
     *             value: string|bool,
     *         }>,
     *     }>,
     *     sequential?: bool,
     *     expiresAt?: string,
     *     signers: list<array{
     *         email: string,
     *         name: string,
     *         roleName?: string,
     *         signingOrder?: int|float,
     *     }>,
     *     ccRecipients?: list<array{
     *         email: string,
     *         name: string,
     *         roleName?: string,
     *     }>,
     *     externalId?: string,
     *     metadata?: array<string, string>,
     * } $params
     * @param string|null $idempotencyKey Reuse the same key when you retry the same request; generated when omitted.
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function create(array $params, ?string $idempotencyKey = null): Envelope
    {
        return self::one($this->transport->send('POST', '/api/v1/envelopes', [
            'json' => self::normalize($params),
            'idempotencyKey' => $idempotencyKey ?? Jws::uuid(),
        ]), Envelope::class);
    }

    /**
     * Envelope and per-recipient state, with version. Always authoritative after a missed or doubtful webhook.
     *
     * Scopes: signing:read.
     *
     * `GET /api/v1/envelopes/{id}` (envelopes.get)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function get(string $id): Envelope
    {
        return self::one($this->transport->send('GET', self::path('/api/v1/envelopes/%s', $id)), Envelope::class);
    }

    /**
     * The signed PDF. 409 NOT_READY until the envelope is COMPLETED. ETag is the SHA-256 of the file.
     *
     * Scopes: documents:download.
     *
     * `GET /api/v1/envelopes/{id}/documents/combined` (envelopes.downloadSigned)
     *
     * @param string|null $ifNoneMatch The ETag of your copy (Download::etag()); the result isNotModified() when it is current.
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function downloadSigned(string $id, ?string $ifNoneMatch = null): Download
    {
        $headers = ['Accept' => 'application/pdf'];
        if ($ifNoneMatch !== null) {
            $headers['If-None-Match'] = $ifNoneMatch;
        }

        return new Download($this->transport->send('GET', self::path('/api/v1/envelopes/%s/documents/combined', $id), [
            'headers' => $headers,
        ]));
    }

    /**
     * Correct a recipient's email or name before they sign. A new email revokes their old link and invites the new address; it is refused once they have verified their email.
     *
     * Scopes: signing:manage.
     *
     * `PATCH /api/v1/envelopes/{id}/recipients/{recipientId}` (envelopes.updateRecipient)
     *
     * @param array{
     *     email?: string,
     *     name?: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function updateRecipient(string $id, string $recipientId, array $params = []): Envelope
    {
        return self::one($this->transport->send('PATCH', self::path('/api/v1/envelopes/%s/recipients/%s', $id, $recipientId), [
            'json' => (object) self::normalize($params),
        ]), Envelope::class);
    }

    /**
     * Email a new signing link to recipients waiting to sign, or to one of them. Earlier links keep working. Limited to one resend per recipient every 10 minutes and 10 invitations a day; 429 with Retry-After when every target is limited.
     *
     * Scopes: signing:manage.
     *
     * `POST /api/v1/envelopes/{id}/resend` (envelopes.resend)
     *
     * @param array{
     *     recipientId?: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function resend(string $id, array $params = []): Envelope
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/envelopes/%s/resend', $id), [
            'json' => (object) self::normalize($params),
        ]), Envelope::class);
    }

    /**
     * Cancel a SENT or IN_PROGRESS envelope. Pending signers are emailed and their links stop working.
     *
     * Scopes: signing:manage.
     *
     * `POST /api/v1/envelopes/{id}/void` (envelopes.void)
     *
     * @param array{
     *     reason: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function void(string $id, array $params): Envelope
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/envelopes/%s/void', $id), [
            'json' => self::normalize($params),
        ]), Envelope::class);
    }
}
