<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.0.0.

namespace PactivaSign\Resource;

use PactivaSign\Model\DocumentFromTemplate;
use PactivaSign\Model\Template;
use PactivaSign\Model\TemplateRole;
use PactivaSign\Pagination\Paginator;

/**
 * Reusable documents with fields and roles.
 *
 * @see \PactivaSign\Client
 */
final class Templates extends ApiResource
{
    /**
     * List templates in the current organization
     *
     * Archived templates are omitted unless status is ARCHIVED or ALL.
     *
     * Scopes: templates:read.
     *
     * `GET /api/v1/document-templates` (templates.list)
     *
     * @param array{
     *     limit?: int|float,
     *     page?: int|float,
     *     status?: 'ACTIVE'|'ARCHIVED'|'ALL',
     * } $query
     * @return Paginator<Template>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function list(array $query = []): Paginator
    {
        $query = self::query($query);

        return new Paginator(fn (?string $cursor) => self::page(
            $this->transport->send('GET', '/api/v1/document-templates', ['query' => $cursor === null ? $query : ['cursor' => $cursor] + $query]),
            Template::class,
        ));
    }

    /**
     * Get template with fields and file info
     *
     * Scopes: templates:read.
     *
     * `GET /api/v1/document-templates/{id}` (templates.get)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function get(string $id): Template
    {
        return self::one($this->transport->send('GET', self::path('/api/v1/document-templates/%s', $id)), Template::class);
    }

    /**
     * Create a document from a template
     *
     * Copies fields and, for each template role, an empty recipient slot (GET /documents/:id/recipients). Fill name and email, keeping templateRoleId. suggestedSequential is true when imported signing orders differ; pass that as sequential when sending.
     *
     * Scopes: documents:create, templates:read.
     *
     * `POST /api/v1/document-templates/{id}/documents` (templates.createDocument)
     *
     * @param array{
     *     name: string,
     * } $params
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function createDocument(string $id, array $params): DocumentFromTemplate
    {
        return self::one($this->transport->send('POST', self::path('/api/v1/document-templates/%s/documents', $id), [
            'json' => self::normalize($params),
        ]), DocumentFromTemplate::class);
    }

    /**
     * List the template roles (signers and CCs) in display order
     *
     * Scopes: templates:read.
     *
     * `GET /api/v1/document-templates/{id}/roles` (templates.listRoles)
     *
     * @return list<TemplateRole>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function listRoles(string $id): array
    {
        return self::many($this->transport->send('GET', self::path('/api/v1/document-templates/%s/roles', $id)), TemplateRole::class);
    }
}
