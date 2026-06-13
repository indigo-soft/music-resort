<?php

declare(strict_types=1);

namespace MusicResort\Http;

use MusicResort\Exception\HttpRequestException;

/**
 * cURL-based HTTP client.
 *
 * Replaced the file_get_contents implementation because PHP's stream wrapper
 * uses a different TLS fingerprint than libcurl, which caused Last.fm's WAF
 * to return error 11 ("Access Denied") even with a valid API key. curl_exec
 * is the de-facto standard for outbound API calls in PHP CLI and has no such
 * issues.
 *
 * DI: receive timeout/userAgent via constructor (ADR-0002).
 * Instantiate only in bin/console.
 */
final readonly class PhpHttpClient implements HttpClientInterface
{
    public function __construct(
        private int $timeoutSeconds = 10,
        private string $userAgent = 'music-resort/1.0 (+https://github.com/indigo-soft/music-resort)',
    ) {}

    public function get(string $url, array $query = []): string
    {
        $fullUrl = $query === []
            ? $url
            : $url . '?' . http_build_query($query);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body      = curl_exec($ch);
        $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($body === false || $curlError !== '') {
            throw new HttpRequestException(sprintf('HTTP request failed (transport error or timeout): %s — %s', $url, $curlError), );
        }

        if ($status < 200 || $status >= 300) {
            throw new HttpRequestException(sprintf('HTTP request returned status %d: %s', $status, $url), );
        }

        return (string)$body;
    }
}
