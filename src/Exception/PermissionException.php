<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/** The client is authenticated but not allowed to do this (403, insufficient_scope). */
class PermissionException extends ApiException
{
}
