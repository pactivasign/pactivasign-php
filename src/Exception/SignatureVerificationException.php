<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/**
 * A webhook request did not carry a valid, recent Pactiva-Signature. Answer it with 400 and do not
 * process it.
 */
final class SignatureVerificationException extends \RuntimeException implements PactivaException
{
}
