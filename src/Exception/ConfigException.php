<?php

declare(strict_types=1);

namespace Root\MusicLocal\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when there is a problem with application configuration.
 */
final class ConfigException extends RuntimeException
{
    public function __construct(string $message = 'Configuration error.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
