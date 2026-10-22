<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/** Credentials were missing, invalid, expired or revoked (401, and OAuth token endpoint failures). */
class AuthenticationException extends ApiException
{
}
