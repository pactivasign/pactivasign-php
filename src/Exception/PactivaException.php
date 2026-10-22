<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/**
 * Marker for every exception this SDK throws. Catch it to handle all PactivaSign failures at once.
 */
interface PactivaException extends \Throwable
{
}
