<?php

declare(strict_types=1);

namespace PactivaSign\Exception;

/** The client was set up wrongly: a missing or unusable key, an invalid URL, an unsupported algorithm. */
final class ConfigurationException extends \InvalidArgumentException implements PactivaException
{
}
