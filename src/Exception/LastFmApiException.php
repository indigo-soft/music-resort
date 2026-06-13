<?php

declare(strict_types=1);

namespace MusicResort\Exception;

use RuntimeException;

/**
 * Thrown when the Last.fm API returns an application-level error
 * (error code in the JSON payload) or an unparseable response.
 */
final class LastFmApiException extends RuntimeException {}
