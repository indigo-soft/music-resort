<?php

declare(strict_types=1);

namespace MusicResort\Database\Repository;

use MusicResort\Enum\FileMetadataStatus;
use PDO;

/**
 * Persistence layer for the music_file_metadata table.
 *
 * Stores the inventory of music files and their current tag data as read by
 * MusicMetadataService (getID3). One row per unique file_path; upsert semantics
 * via INSERT OR REPLACE keep the inventory current across repeated scans.
 *
 * All tag data is written here — no direct getID3 calls from this class (ADR-0003).
 *
 * DI: receive \PDO via constructor (ADR-0002). Instantiate only in bin/console.
 */
final class MusicFileMetadataRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * Insert or replace the metadata row for a file.
     *
     * Called after every successful getID3 read in MusicMetadataService.
     * Overwrites all tag columns and refreshes scanned_at.
     *
     * @param array<string, mixed> $data keys must match column names exactly
     */
    public function upsert(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO music_file_metadata
                (file_path, status, format, duration, bitrate, file_size,
                 title, artist, album, album_artist, track_number, year,
                 genre, comment, tag_source, scanned_at)
             VALUES
                (:file_path, :status, :format, :duration, :bitrate, :file_size,
                 :title, :artist, :album, :album_artist, :track_number, :year,
                 :genre, :comment, :tag_source, NOW())
             ON DUPLICATE KEY UPDATE
                status       = VALUES(status),
                format       = VALUES(format),
                duration     = VALUES(duration),
                bitrate      = VALUES(bitrate),
                file_size    = VALUES(file_size),
                title        = VALUES(title),
                artist       = VALUES(artist),
                album        = VALUES(album),
                album_artist = VALUES(album_artist),
                track_number = VALUES(track_number),
                year         = VALUES(year),
                genre        = VALUES(genre),
                comment      = VALUES(comment),
                tag_source   = VALUES(tag_source),
                scanned_at   = NOW()',
        );

        $stmt->execute([
            ':file_path'    => $data['file_path'],
            ':status'       => $data['status'] ?? FileMetadataStatus::Active->value,
            ':format'       => $data['format']       ?? null,
            ':duration'     => $data['duration']     ?? null,
            ':bitrate'      => $data['bitrate']      ?? null,
            ':file_size'    => $data['file_size']    ?? null,
            ':title'        => $data['title']        ?? null,
            ':artist'       => $data['artist']       ?? null,
            ':album'        => $data['album']        ?? null,
            ':album_artist' => $data['album_artist'] ?? null,
            ':track_number' => $data['track_number'] ?? null,
            ':year'         => $data['year']         ?? null,
            ':genre'        => $data['genre']        ?? null,
            ':comment'      => $data['comment']      ?? null,
            ':tag_source'   => $data['tag_source']   ?? null,
        ]);
    }

    /**
     * Mark a file as missing (file no longer found at its recorded path).
     *
     * @param string $filePath
     */
    public function markMissing(string $filePath): void
    {
        $this->updateStatus($filePath, FileMetadataStatus::Missing);
    }

    /**
     * Mark a file as unreadable (getID3 failed to parse it).
     *
     * @param string $filePath
     */
    public function markUnreadable(string $filePath): void
    {
        $this->updateStatus($filePath, FileMetadataStatus::Unreadable);
    }

    /**
     * Find metadata record by exact file path.
     *
     * @param string $filePath
     * @return array<string, mixed>|null
     */
    public function findByPath(string $filePath): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM music_file_metadata WHERE file_path = :file_path',
        );

        $stmt->execute([':file_path' => $filePath]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Return all active records for a given artist, ordered by album and track number.
     *
     * @param string $artist
     * @return list<array<string, mixed>>
     */
    public function findByArtist(string $artist): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM music_file_metadata
             WHERE artist = :artist
               AND status = 'active'
             ORDER BY album ASC, track_number ASC",
        );

        $stmt->execute([':artist' => $artist]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all active records not scanned since the given UTC datetime string.
     *
     * Useful for incremental rescans: find files whose metadata is stale.
     *
     * @param string $before UTC datetime, e.g. "2026-05-01 00:00:00"
     * @return list<array<string, mixed>>
     */
    public function findStaleActive(string $before): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM music_file_metadata
             WHERE status = 'active'
               AND (scanned_at IS NULL OR scanned_at < :before)
             ORDER BY scanned_at ASC",
        );

        $stmt->execute([':before' => $before]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count rows grouped by status.
     *
     * @return array<string, int> e.g. ['active' => 1200, 'missing' => 3]
     */
    public function countByStatus(): array
    {
        $stmt = $this->pdo->query(
            'SELECT status, COUNT(*) as cnt FROM music_file_metadata GROUP BY status',
        );

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(string)$row['status']] = (int)$row['cnt'];
        }

        return $result;
    }

    /**
     * Return distinct artist values from active records, sorted alphabetically.
     *
     * @return list<string>
     */
    public function findAllArtists(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT COALESCE(NULLIF(album_artist, ''), artist) AS resolved_artist
             FROM music_file_metadata
             WHERE status = 'active'
               AND (artist IS NOT NULL OR album_artist IS NOT NULL)
             ORDER BY resolved_artist ASC",
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function updateStatus(string $filePath, FileMetadataStatus $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE music_file_metadata
             SET status = :status
             WHERE file_path = :file_path',
        );

        $stmt->execute([
            ':status'    => $status->value,
            ':file_path' => $filePath,
        ]);
    }
}
