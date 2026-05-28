<?php

declare(strict_types=1);

namespace MusicResort\Database\Repository;

use MusicResort\Enum\AudioProcessingStatus;
use MusicResort\Model\AudioProcessingRecord;
use PDO;

/**
 * Persistence layer for the audio_processing table.
 *
 * All public methods accept/return AudioProcessingRecord value objects.
 * Raw SQL stays inside this class — no SQL leaks into services.
 *
 * DI: receive \PDO via constructor (ADR-0002). Instantiate only in bin/console.
 */
final class AudioProcessingRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * Persist a new record and return its auto-generated id.
     *
     * @param AudioProcessingRecord $record
     */
    public function insert(AudioProcessingRecord $record): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audio_processing
                (original_path, processed_path, status, operation,
                 duration_before, duration_after, size_before, size_after,
                 error_message, processed_at)
             VALUES
                (:original_path, :processed_path, :status, :operation,
                 :duration_before, :duration_after, :size_before, :size_after,
                 :error_message, :processed_at)',
        );

        $stmt->execute([
            ':original_path'  => $record->originalPath,
            ':processed_path' => $record->processedPath,
            ':status'         => $record->status->value,
            ':operation'      => $record->operation->value,
            ':duration_before' => $record->durationBefore,
            ':duration_after'  => $record->durationAfter,
            ':size_before'    => $record->sizeBefore,
            ':size_after'     => $record->sizeAfter,
            ':error_message'  => $record->errorMessage,
            ':processed_at'   => $record->processedAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update status (and optional error message) for an existing record.
     * Sets processed_at to current UTC time.
     *
     * @param int $id
     * @param AudioProcessingStatus $status
     * @param ?string $errorMessage
     */
    public function updateStatus(
        int $id,
        AudioProcessingStatus $status,
        ?string $errorMessage = null,
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE audio_processing
             SET status        = :status,
                 error_message = :error_message,
                 processed_at  = datetime(\'now\')
             WHERE id = :id',
        );

        $stmt->execute([
            ':status'        => $status->value,
            ':error_message' => $errorMessage,
            ':id'            => $id,
        ]);
    }

    /**
     * Find the most recent record for a given original file path, or null if none.
     *
     * @param string $originalPath
     */
    public function findByOriginalPath(string $originalPath): ?AudioProcessingRecord
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM audio_processing
             WHERE original_path = :path
             ORDER BY created_at DESC
             LIMIT 1',
        );

        $stmt->execute([':path' => $originalPath]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? AudioProcessingRecord::fromRow($row) : null;
    }

    /**
     * Return all records with the given status, newest first.
     *
     * @param AudioProcessingStatus $status
     * @return AudioProcessingRecord[]
     */
    public function findByStatus(AudioProcessingStatus $status): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM audio_processing
             WHERE status = :status
             ORDER BY created_at DESC',
        );

        $stmt->execute([':status' => $status->value]);

        return array_map(
            AudioProcessingRecord::fromRow(...),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    /**
     * Count records grouped by status.
     *
     * @return array<string, int> e.g. ['pending' => 12, 'processed' => 34]
     */
    public function countByStatus(): array
    {
        $stmt = $this->pdo->query(
            'SELECT status, COUNT(*) as cnt FROM audio_processing GROUP BY status',
        );

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(string)$row['status']] = (int)$row['cnt'];
        }

        return $result;
    }
}
