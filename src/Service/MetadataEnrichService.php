<?php

declare(strict_types=1);

namespace MusicResort\Service;

use MusicResort\Database\Repository\LastFmTagRepository;
use MusicResort\Database\Repository\MusicFileMetadataRepository;
use MusicResort\Exception\HttpRequestException;
use MusicResort\Exception\LastFmApiException;
use MusicResort\Logger\LoggerInterface;
use Throwable;

/**
 * Orchestrates Last.fm metadata enrichment.
 *
 * Pipeline per run:
 *   1. Collect distinct artists from music_file_metadata (active rows).
 *   2. Skip artists with a fresh cache row (fetched_at within TTL),
 *      unless $force is set.
 *   3. Fetch top tags from Last.fm for the remaining artists and upsert
 *      them into lastfm_artist_tags.
 *
 * Error policy (agreed 2026-06-12): one retry per artist on transport /
 * API failure, then skip + warning log and continue. A failed artist never
 * aborts the run.
 *
 * Rate limiting: fixed 250 ms pause between live API calls (cache hits do
 * not pause) — keeps the client well under Last.fm's ~5 req/s guidance.
 *
 * DI: all collaborators via constructor (ADR-0002). Instantiate only in
 * bin/console.
 */
final class MetadataEnrichService
{
    private const int API_PAUSE_MICROSECONDS = 250_000;

    public function __construct(
        private readonly MusicFileMetadataRepository $metadataRepository,
        private readonly LastFmTagRepository $tagRepository,
        private readonly LastFmClient $lastFm,
        private readonly LoggerInterface $logger,
        private readonly int $cacheTtlDays,
    ) {}

    /**
     * Run the enrichment pipeline.
     *
     * @param bool $force re-fetch even when a fresh cache row exists
     * @param int|null $limit process at most N artists (null = all)
     * @param callable(string $event, string $artist, array<string, mixed> $context): void|null $onProgress
     *                                                                                                      optional progress callback; $event is one of: 'fetched', 'cached', 'empty', 'failed'
     * @param ?callable $onProgress
     * @return array{total: int, fetched: int, cached: int, empty: int, failed: int}
     */
    public function enrich(bool $force = false, ?int $limit = null, ?callable $onProgress = null): array
    {
        $artists = $this->metadataRepository->findAllArtists();

        if ($limit !== null && $limit > 0) {
            $artists = array_slice($artists, 0, $limit);
        }

        $summary = [
            'total'   => count($artists),
            'fetched' => 0,
            'cached'  => 0,
            'empty'   => 0,
            'failed'  => 0,
        ];

        foreach ($artists as $artist) {
            if (!$force && $this->tagRepository->hasFresh($artist, $this->cacheTtlDays)) {
                $summary['cached']++;
                $this->report($onProgress, 'cached', $artist);

                continue;
            }

            $tags = $this->fetchWithRetry($artist);

            if ($tags === null) {
                $summary['failed']++;
                $this->report($onProgress, 'failed', $artist);

                continue;
            }

            $this->tagRepository->upsert($artist, $tags);

            if ($tags === []) {
                $summary['empty']++;
                $this->report($onProgress, 'empty', $artist);
            } else {
                $summary['fetched']++;
                $this->report($onProgress, 'fetched', $artist, ['tags' => count($tags)]);
            }
        }

        $this->logger->info('metadata:enrich finished', $summary);

        return $summary;
    }

    /**
     * Fetch tags with a single retry; null means both attempts failed.
     *
     * @param string $artist
     * @return list<array{name: string, count: int}>|null
     */
    private function fetchWithRetry(string $artist): ?array
    {
        foreach ([1, 2] as $attempt) {
            try {
                usleep(self::API_PAUSE_MICROSECONDS);

                return $this->lastFm->getArtistTopTags($artist);
            } catch (HttpRequestException|LastFmApiException $e) {
                $this->logger->warning('Last.fm fetch failed', [
                    'artist'  => $artist,
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
            } catch (Throwable $e) {
                // Unexpected failure (e.g. JSON encode in repository) — log and skip,
                // never abort the whole run because of one artist.
                $this->logger->error('Unexpected error during enrichment', [
                    'artist'    => $artist,
                    'attempt'   => $attempt,
                    'exception' => $e::class,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * @param callable(string, string, array<string, mixed>): void|null $onProgress
     * @param string $event
     * @param string $artist
     * @param array<string, mixed> $context
     */
    private function report(?callable $onProgress, string $event, string $artist, array $context = []): void
    {
        if ($onProgress !== null) {
            $onProgress($event, $artist, $context);
        }
    }
}
