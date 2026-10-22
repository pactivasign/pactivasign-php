<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Resource;

use PactivaSign\Http\Download;
use PactivaSign\Http\Multipart;
use PactivaSign\Model\AuditEvent;
use PactivaSign\Model\DeletedField;
use PactivaSign\Model\Document;
use PactivaSign\Model\DocumentField;
use PactivaSign\Model\DocumentRecipient;
use PactivaSign\Model\DocumentWithDeliveries;
use PactivaSign\Pagination\Paginator;

/**
 * Drafts: upload, fields, recipients, files and audit trail.
 *
 * @see \PactivaSign\Client
 */
final class Documents extends ApiResource
{
    /**
     * List documents in the current organization, newest first
     *
     * Omit limit, cursor, and page for every row. Pass page (1-based) and optional limit to jump to that page; the response includes total and pageCount. Or pass limit and then nextCursor as cursor.
     *
     * Scopes: documents:read.
     *
     * `GET /api/v1/documents` (documents.list)
     *
     * @param array{
     *     limit?: int|float,
     *     page?: int|float,
     * } $query
     * @return Paginator<DocumentWithDeliveries>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function list(array $query = []): Paginator
    {
        $query = self::query($query);

        return new Paginator(fn (?string $cursor) => self::page(
            $this->transport->send('GET', '/api/v1/documents', ['query' => $cursor === null ? $query : ['cursor' => $cursor] + $query]),
            DocumentWithDeliveries::class,
        ));
    }

    /**
     * Create a draft document
     *
     * Scopes: documents:create.
     *
     * `POST /api/v1/documents` (documents.create)
     *
     * @param array{
     *     name: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function create(array $params): Document
    {
        return self::one($this->transport->send('POST', '/api/v1/documents', [
            'json' => self::normalize($params),
        ]), Document::class);
    }

    /**
     * Create a draft and upload PDF/DOC/DOCX, normalizing to a canonical PDF
     *
     * Scopes: documents:create.
     *
     * `POST /api/v1/documents/upload` (documents.upload)
     *
     * @param string|resource|\Psr\Http\Message\StreamInterface $file The PDF: its contents, an open stream or a PSR-7 stream.
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function upload(string $name, mixed $file, string $filename = 'document.pdf'): Document
    {
        return self::one($this->transport->send('POST', '/api/v1/documents/upload', [
            'multipart' => (new Multipart())
                ->field('name', $name)
                ->file('file', $file, $filename),
        ]), Document::class);
    }

    /**
     * Get document metadata (not the PDF bytes)
     *
     * Scopes: documents:read.
     *
     * `GET /api/v1/documents/{id}` (documents.get)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function get(string $id): DocumentWithDeliveries
    {
        return self::one($this->transport->send('GET', self::path('/api/v1/documents/%s', $id)), DocumentWithDeliveries::class);
    }

    /**
     * Soft-delete a document
     *
     * Scopes: documents:delete.
     *
     * `DELETE /api/v1/documents/{id}` (documents.delete)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function delete(string $id): void
    {
        $this->transport->send('DELETE', self::path('/api/v1/documents/%s', $id));
    }

    /**
     * Rename a document
     *
     * Changes the name shown in the list and on later emails. Allowed after create, including once the document has been sent.
     *
     * Scopes: documents:update.
     *
     * `PATCH /api/v1/documents/{id}` (documents.rename)
     *
     * @param array{
     *     name: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function rename(string $id, array $params): Document
    {
        return self::one($this->transport->send('PATCH', self::path('/api/v1/documents/%s', $id), [
            'json' => self::normalize($params),
        ]), Document::class);
    }

    /**
     * List hash-chained audit events for a document
     *
     * Scopes: documents:read.
     *
     * `GET /api/v1/documents/{id}/audit` (documents.getAuditTrail)
     *
     * @return list<AuditEvent>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function getAuditTrail(string $id): array
    {
        return self::many($this->transport->send('GET', self::path('/api/v1/documents/%s/audit', $id)), AuditEvent::class);
    }

    /**
     * Download the audit trail as a PDF
     *
     * Activity history for the document: who did what, and when. This is not the certificate of completion.
     *
     * Scopes: documents:read.
     *
     * `GET /api/v1/documents/{id}/audit/report` (documents.downloadAuditReport)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function downloadAuditReport(string $id): Download
    {
        return new Download($this->transport->send('GET', self::path('/api/v1/documents/%s/audit/report', $id), [
            'headers' => ['Accept' => 'application/pdf'],
        ]));
    }

    /**
     * List fields for the latest document version
     *
     * Scopes: documents:read.
     *
     * `GET /api/v1/documents/{id}/fields` (documents.listFields)
     *
     * @return list<DocumentField>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function listFields(string $id): array
    {
        return self::many($this->transport->send('GET', self::path('/api/v1/documents/%s/fields', $id)), DocumentField::class);
    }

    /**
     * Create a field on the latest document version
     *
     * Scopes: documents:update.
     *
     * `POST /api/v1/documents/{id}/fields` (documents.createField)
     *
     * @param array{
     *     type: 'SIGNATURE'|'INITIAL'|'NAME'|'EMAIL'|'DATE'|'TEXT'|'CHECKBOX'|'RADIO'|'DROPDOWN'|\PactivaSign\Model\FieldType,
     *     page: int|float,
     *     x: int|float,
     *     y: int|float,
     *     width: int|float,
     *     height: int|float,
     *     required?: bool,
     *     signerId?: string,
     *     recipientEmail?: string,
     *     fontFamily?: 'ARIAL'|'CALIBRI'|'COURIER_NEW'|'LUCIDA_CONSOLE'|'TAHOMA'|'TIMES_NEW_ROMAN'|'TREBUCHET'|'VERDANA',
     *     fontSize?: int|float,
     *     bold?: bool,
     *     italic?: bool,
     *     underline?: bool,
     *     defaultValue?: string|bool,
     *     metadata?: array<string, mixed>,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function createField(string $id, array $params): DocumentField
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/fields', $id), [
            'json' => self::normalize($params),
        ]), DocumentField::class);
    }

    /**
     * Delete a field from the document
     *
     * Scopes: documents:update.
     *
     * `DELETE /api/v1/documents/{id}/fields/{fieldId}` (documents.deleteField)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function deleteField(string $id, string $fieldId): DeletedField
    {
        return self::one($this->transport->send('DELETE', self::path('/api/v1/documents/%s/fields/%s', $id, $fieldId)), DeletedField::class);
    }

    /**
     * Update a field on the latest document version
     *
     * Scopes: documents:update.
     *
     * `PATCH /api/v1/documents/{id}/fields/{fieldId}` (documents.updateField)
     *
     * @param array{
     *     type?: 'SIGNATURE'|'INITIAL'|'NAME'|'EMAIL'|'DATE'|'TEXT'|'CHECKBOX'|'RADIO'|'DROPDOWN'|\PactivaSign\Model\FieldType,
     *     page?: int|float,
     *     x?: int|float,
     *     y?: int|float,
     *     width?: int|float,
     *     height?: int|float,
     *     required?: bool,
     *     signerId?: string,
     *     recipientEmail?: string,
     *     fontFamily?: 'ARIAL'|'CALIBRI'|'COURIER_NEW'|'LUCIDA_CONSOLE'|'TAHOMA'|'TIMES_NEW_ROMAN'|'TREBUCHET'|'VERDANA',
     *     fontSize?: int|float,
     *     bold?: bool,
     *     italic?: bool,
     *     underline?: bool,
     *     defaultValue?: string|bool,
     *     metadata?: array<string, mixed>,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function updateField(string $id, string $fieldId, array $params = []): DocumentField
    {
        return self::one($this->transport->send('PATCH', self::path('/api/v1/documents/%s/fields/%s', $id, $fieldId), [
            'json' => (object) self::normalize($params),
        ]), DocumentField::class);
    }

    /**
     * Upload PDF/DOC/DOCX and normalize it to a canonical PDF in Azure Blob Storage
     *
     * Scopes: documents:create.
     *
     * `POST /api/v1/documents/{id}/file` (documents.uploadFile)
     *
     * @param string|resource|\Psr\Http\Message\StreamInterface $file The PDF: its contents, an open stream or a PSR-7 stream.
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function uploadFile(string $id, mixed $file, string $filename = 'document.pdf'): Document
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/documents/%s/file', $id), [
            'multipart' => (new Multipart())
                ->file('file', $file, $filename),
        ]), Document::class);
    }

    /**
     * Stream the original or signed PDF through the API (preferred for browser preview; no Azure SAS)
     *
     * Scopes: documents:download.
     *
     * `GET /api/v1/documents/{id}/file/content` (documents.downloadFile)
     *
     * @param 'original'|'signed'|null $variant original = uploaded PDF. signed = stamped PDF after COMPLETED. Omit to prefer signed when it exists.
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function downloadFile(string $id, ?string $variant = null): Download
    {
        return new Download($this->transport->send('GET', self::path('/api/v1/documents/%s/file/content', $id), [
            'query' => self::query(['variant' => $variant]),
            'headers' => ['Accept' => 'application/pdf'],
        ]));
    }

    /**
     * Recipients saved on a document before sending
     *
     * A document created from a template includes one slot per role. email and name are null until filled. templateRole carries the imported name, action and the role’s own signing order.
     *
     * Scopes: documents:read.
     *
     * `GET /api/v1/documents/{id}/recipients` (documents.listRecipients)
     *
     * @return list<DocumentRecipient>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function listRecipients(string $id): array
    {
        return self::many($this->transport->send('GET', self::path('/api/v1/documents/%s/recipients', $id)), DocumentRecipient::class);
    }

    /**
     * Save the recipient list on a draft (replaces the whole list)
     *
     * Allowed while DRAFT or PREPARING. POST /documents/:id/signing-requests without `signers` sends to this list. When filling a template slot, pass its templateRoleId so fields stay linked to that person. Omit signingOrder to keep the order imported with the role.
     *
     * Scopes: documents:update.
     *
     * `PUT /api/v1/documents/{id}/recipients` (documents.replaceRecipients)
     *
     * @param array{
     *     recipients: list<array{
     *         email: string,
     *         name: string,
     *         role: 'SIGNER'|'CC'|'CUSTOM'|\PactivaSign\Model\DocumentRecipientRole,
     *         customRoleId?: string,
     *         templateRoleId?: string,
     *         signingOrder?: int|float,
     *     }>,
     * } $params
     * @return list<DocumentRecipient>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function replaceRecipients(string $id, array $params): array
    {
        return self::many($this->transport->send('PUT', self::path('/api/v1/documents/%s/recipients', $id), [
            'json' => self::normalize($params),
        ]), DocumentRecipient::class);
    }
}
