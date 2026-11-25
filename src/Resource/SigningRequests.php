<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v2.0.0.

namespace PactivaSign\Resource;

use PactivaSign\Auth\Jws;
use PactivaSign\Model\SigningCorrection;
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
     * Recipients and fields of a sent document, ready to correct. correctable is false once someone has signed. Documents sent inside an envelope use GET /envelopes/:id/correction.
     *
     * Scopes: signing:manage.
     *
     * `GET /api/v1/documents/{id}/signing-request/correction` (signingRequests.getCorrection)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function getCorrection(string $id): SigningCorrection
    {
        return self::one($this->transport->send('GET', self::path('/api/v1/documents/%s/signing-request/correction', $id)), SigningCorrection::class);
    }

    /**
     * Correct a sent document that nobody has signed: replace its recipients and fields in one step. Signers keep their link and see the change when they next load it. Only new recipients and changed addresses are emailed. Documents sent inside an envelope use PUT /envelopes/:id/correction.
     *
     * Scopes: signing:manage.
     *
     * `PUT /api/v1/documents/{id}/signing-request/correction` (signingRequests.correct)
     *
     * @param array{
     *     recipients: list<array{
     *         id?: string,
     *         ref?: string,
     *         email: string,
     *         name: string,
     *         role: 'SIGNER'|'CC'|'CUSTOM'|\PactivaSign\Model\DocumentRecipientRole,
     *         customRoleId?: string,
     *         signingOrder?: int|float,
     *     }>,
     *     fields: list<array{
     *         type: 'SIGNATURE'|'INITIAL'|'NAME'|'EMAIL'|'DATE'|'TEXT'|'CHECKBOX'|'RADIO'|'DROPDOWN'|\PactivaSign\Model\FieldType,
     *         page: int|float,
     *         x: int|float,
     *         y: int|float,
     *         width: int|float,
     *         height: int|float,
     *         required?: bool,
     *         fontFamily?: 'ARIAL'|'CALIBRI'|'COURIER_NEW'|'LUCIDA_CONSOLE'|'TAHOMA'|'TIMES_NEW_ROMAN'|'TREBUCHET'|'VERDANA',
     *         fontSize?: int|float,
     *         bold?: bool,
     *         italic?: bool,
     *         underline?: bool,
     *         defaultValue?: string|bool,
     *         metadata?: array<string, mixed>,
     *         id?: string,
     *         recipient?: string|null,
     *     }>,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function correct(string $id, array $params): SigningCorrection
    {
        return self::one($this->transport->send('PUT', self::path('/api/v1/documents/%s/signing-request/correction', $id), [
            'json' => self::normalize($params),
        ]), SigningCorrection::class);
    }

    /**
     * Correct a recipient's email or name before they sign, on a document sent on its own. A new email revokes their old link and invites the new address; it is refused once they have verified their email. Documents sent inside an envelope use PATCH /envelopes/:id/recipients/:recipientId.
     *
     * Scopes: signing:manage.
     *
     * `PATCH /api/v1/documents/{id}/signing-request/recipients/{recipientId}` (signingRequests.updateRecipient)
     *
     * @param array{
     *     email?: string,
     *     name?: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function updateRecipient(string $id, string $recipientId, array $params = []): SigningRequest
    {
        return self::one($this->transport->send('PATCH', self::path('/api/v1/documents/%s/signing-request/recipients/%s', $id, $recipientId), [
            'json' => (object) self::normalize($params),
        ]), SigningRequest::class);
    }

    /**
     * Email a reminder with a new signing link to recipients waiting to sign, or to one of them. Uses reminder email copy. Earlier links keep working. Shares the resend rate limit (one per recipient every 10 minutes and 10 a day); 429 with Retry-After when every target is limited. Does not count toward automatic maxReminders. Documents sent inside an envelope are reminded with POST /envelopes/:id/remind.
     *
     * Scopes: signing:manage.
     *
     * `POST /api/v1/documents/{id}/signing-request/remind` (signingRequests.remind)
     *
     * @param array{
     *     recipientId?: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function remind(string $id, array $params = []): SigningRequest
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/signing-request/remind', $id), [
            'json' => (object) self::normalize($params),
        ]), SigningRequest::class);
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
     *     sequential?: bool,
     *     expiresAt?: string|null,
     *     requireSignerOtp?: bool,
     *     message?: string|null,
     *     reminders?: array{
     *         enabled?: bool,
     *         firstReminderAfterDays?: int|float,
     *         repeatEveryDays?: int|float,
     *         maxReminders?: int|float,
     *     },
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
     * @param string|null $idempotencyKey Reuse the same key when you retry the same request; generated when omitted.
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function create(string $id, array $params = [], ?string $idempotencyKey = null): SigningRequest
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/signing-requests', $id), [
            'json' => (object) self::normalize($params),
            'idempotencyKey' => $idempotencyKey ?? Jws::uuid(),
        ]), SigningRequest::class);
    }

    /**
     * Cancel a SENT or IN_PROGRESS document that was sent on its own. Signers who were invited and have not signed are emailed, and all its signing links stop working. Documents sent inside an envelope are voided with POST /envelopes/:id/void.
     *
     * Scopes: signing:manage.
     *
     * `POST /api/v1/documents/{id}/void` (signingRequests.void)
     *
     * @param array{
     *     reason: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function void(string $id, array $params): SigningRequest
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/void', $id), [
            'json' => self::normalize($params),
        ]), SigningRequest::class);
    }
}
