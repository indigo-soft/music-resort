<?php

declare(strict_types=1);

namespace MusicResort\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when there is a problem with application configuration.
 */
final class MusicMetadataException extends RuntimeException
{
    public function __construct(string $message = 'MetaData error.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
