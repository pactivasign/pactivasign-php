<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Resource;

use PactivaSign\Model\SandboxEmail;
use PactivaSign\Pagination\Paginator;

/**
 * Sandbox-only helpers for testing an integration. Not available in production.
 *
 * @see \PactivaSign\Client
 */
final class Sandbox extends ApiResource
{
    /**
     * Sandbox only: emails captured instead of sent, newest first
     *
     * In the sandbox, email goes only to verified members of your organization. Mail to anyone else (test signers, CC recipients) is captured here instead, with its signing link in actionUrl and one-time codes in code, so tests can complete a signing flow. Returns 404 outside the sandbox.
     *
     * Scopes: signing:read.
     *
     * `GET /api/v1/sandbox/emails` (sandbox.listEmails)
     *
     * @param array{
     *     limit?: int|float,
     *     page?: int|float,
     *     to?: string,
     *     type?: 'SIGNING_INVITATION'|'OTP'|'REMINDER'|'COMPLETION'|'CARBON_COPY'|'DECLINED'|'ACCOUNT_VERIFICATION'|'VOIDED'|'EXPIRED'|\PactivaSign\Model\SandboxEmailType,
     *     signingRequestId?: string,
     * } $query
     * @return Paginator<SandboxEmail>
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function listEmails(array $query = []): Paginator
    {
        $query = self::query($query);

        return new Paginator(fn (?string $cursor) => self::page(
            $this->transport->send('GET', '/api/v1/sandbox/emails', ['query' => $cursor === null ? $query : ['cursor' => $cursor] + $query]),
            SandboxEmail::class,
        ));
    }
}
