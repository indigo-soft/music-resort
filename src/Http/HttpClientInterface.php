<?php

declare(strict_types=1);

namespace MusicResort\Http;

use MusicResort\Exception\HttpRequestException;

/**
 * Minimal HTTP client contract for outbound GET requests.
 *
 * Deliberately tiny: the project only needs simple GET calls (Last.fm API),
 * so no PSR-18 / external HTTP packages are introduced (minimal-dependency
 * philosophy, ADR-0004 component whitelist).
 */
interface HttpClientInterface
{
    /**
     * Perform a GET request and return the raw response body.
     *
     * @param string $url base URL without query string
     * @param array<string, int|string> $query query parameters, urlencoded by the implementation
     * @throws HttpRequestException on transport failure or non-2xx response
     */
    public function get(string $url, array $query = []): string;
}
