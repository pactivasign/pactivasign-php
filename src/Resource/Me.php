<?php

declare(strict_types=1);

// @generated from openapi/public.json; do not edit. Run `composer generate`. Source: openapi-v1.1.0.

namespace PactivaSign\Resource;

use PactivaSign\Model\Me as MeModel;

/**
 * Who is calling: organization, environment, scopes and limits.
 *
 * @see \PactivaSign\Client
 */
final class Me extends ApiResource
{
    /**
     * Who is calling: organization, environment, granted scopes and plan limits
     *
     * Works with any OAuth token, whatever its scopes. SDK connection tests call it.
     *
     * `GET /api/v1/me` (me.get)
     *
     * @throws \PactivaSign\Exception\PactivaException
     */
    public function get(): MeModel
    {
        return self::one($this->transport->send('GET', '/api/v1/me'), MeModel::class);
    }
}
