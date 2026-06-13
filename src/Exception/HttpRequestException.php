<?php

declare(strict_types=1);

namespace MusicResort\Exception;

use RuntimeException;

/**
 * Thrown by HttpClientInterface implementations on transport failure
 * (connection error, timeout) or a non-2xx HTTP response.
 */
final class HttpRequestException extends RuntimeException {}
