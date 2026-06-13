<?php

declare(strict_types=1);

namespace MusicResort\Database\Repository;

use PDO;

/**
 * Persistence layer for the lastfm_artist_tags table.
 *
 * Caches Last.fm artist top tags as a JSON column. One row per unique artist;
 * upsert semantics refresh tags and fetched_at on repeated enrichment runs.
 *
 * TTL is evaluated at query time against fetched_at — there is no background
 * eviction; stale rows are simply re-fetched by MetadataEnrichService.
 *
 * DI: receive \PDO via constructor (ADR-0002). Instantiate only in bin/console.
 */
final class LastFmTagRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * Insert or refresh the cached tags for an artist.
     *
     * @param string $artist
     * @param list<array{name: string, count: int}> $tags
     */
    public function upsert(string $artist, array $tags): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO lastfm_artist_tags (artist, tags, fetched_at)
             VALUES (:artist, :tags, NOW())
             ON DUPLICATE KEY UPDATE
                tags       = VALUES(tags),
                fetched_at = NOW()',
        );

        $stmt->execute([
            ':artist' => $artist,
            ':tags'   => json_encode($tags, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * Find the cached row for an artist (regardless of freshness).
     *
     * @param string $artist
     * @return array{artist: string, tags: list<array{name: string, count: int}>, fetched_at: string}|null
     */
    public function findByArtist(string $artist): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT artist, tags, fetched_at
             FROM lastfm_artist_tags
             WHERE artist = :artist',
        );

        $stmt->execute([':artist' => $artist]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'artist'     => (string)$row['artist'],
            'tags'       => json_decode((string)$row['tags'], true, 512, JSON_THROW_ON_ERROR),
            'fetched_at' => (string)$row['fetched_at'],
        ];
    }

    /**
     * Check whether a fresh (within TTL) cache row exists for an artist.
     *
     * @param string $artist
     * @param int $ttlDays
     */
    public function hasFresh(string $artist, int $ttlDays): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM lastfm_artist_tags
             WHERE artist = :artist
               AND fetched_at >= (NOW() - INTERVAL :ttl_days DAY)
             LIMIT 1',
        );

        $stmt->bindValue(':artist', $artist);
        $stmt->bindValue(':ttl_days', $ttlDays, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Count all cached artists.
     */
    public function countAll(): int
    {
        return (int)$this->pdo
            ->query('SELECT COUNT(*) FROM lastfm_artist_tags')
            ->fetchColumn();
    }
}
