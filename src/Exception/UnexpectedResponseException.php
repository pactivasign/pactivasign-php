<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/** A successful response did not have the shape the API contract promises. */
final class UnexpectedResponseException extends \UnexpectedValueException implements PactivaException
{
}
