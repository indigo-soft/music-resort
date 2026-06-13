<?php

declare(strict_types=1);

namespace MusicResort\Service;

use JsonException;
use MusicResort\Exception\HttpRequestException;
use MusicResort\Exception\LastFmApiException;
use MusicResort\Http\HttpClientInterface;

/**
 * Thin client for the Last.fm REST API (artist.gettoptags method).
 *
 * Returns normalized tag lists: lowercased tag names with their Last.fm
 * relevance counts (0–100), sorted by count descending. The counts are
 * preserved because mood resolution (organize pipeline) uses them as
 * priority weights.
 *
 * DI: receive apiKey/apiUrl/HttpClientInterface via constructor (ADR-0002).
 * Instantiate only in bin/console.
 */
final class LastFmClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly HttpClientInterface $http,
    ) {}

    /**
     * Fetch top tags for an artist.
     *
     * @param string $artist
     * @return list<array{name: string, count: int}> sorted by count descending;
     *                                               empty list when Last.fm has no tags
     * @throws HttpRequestException on transport failure or non-2xx response
     * @throws LastFmApiException on Last.fm error payload or malformed JSON
     */
    public function getArtistTopTags(string $artist): array
    {
        $body = $this->http->get($this->apiUrl, [
            'method'      => 'artist.gettoptags',
            'artist'      => $artist,
            'api_key'     => $this->apiKey,
            'format'      => 'json',
            'autocorrect' => 1,
        ]);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new LastFmApiException(sprintf('Last.fm returned malformed JSON for artist "%s": %s', $artist, $e->getMessage()), previous: $e, );
        }

        if (isset($data['error'])) {
            throw new LastFmApiException(sprintf('Last.fm API error %d for artist "%s": %s', (int)$data['error'], $artist, (string)($data['message'] ?? 'unknown error'), ));
        }

        $rawTags = $data['toptags']['tag'] ?? [];

        // Single-tag responses arrive as an object, not a list.
        if (isset($rawTags['name'])) {
            $rawTags = [$rawTags];
        }

        $tags = [];

        foreach ($rawTags as $tag) {
            $name = mb_strtolower(trim((string)($tag['name'] ?? '')));

            if ($name === '') {
                continue;
            }

            $tags[] = [
                'name'  => $name,
                'count' => (int)($tag['count'] ?? 0),
            ];
        }

        usort($tags, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);

        return $tags;
    }
}
