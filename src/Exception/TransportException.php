<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/** The request never got an HTTP response (DNS, TLS, connection reset, timeout). */
final class TransportException extends \RuntimeException implements PactivaException
{
}
