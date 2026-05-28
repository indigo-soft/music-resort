<?php

declare(strict_types=1);

namespace MusicResort\Model;

use MusicResort\Enum\AudioProcessingOperation;
use MusicResort\Enum\AudioProcessingStatus;

/**
 * Immutable record of a single audio processing operation.
 *
 * Produced by AudioProcessingRepository::findBy*() methods.
 * Created by callers via the named constructor fromRow() or directly via new.
 */
final readonly class AudioProcessingRecord
{
    public function __construct(
        public ?int $id,
        public string $originalPath,
        public ?string $processedPath,
        public AudioProcessingStatus $status,
        public AudioProcessingOperation $operation,
        public ?float $durationBefore,
        public ?float $durationAfter,
        public ?int $sizeBefore,
        public ?int $sizeAfter,
        public ?string $errorMessage,
        public ?string $processedAt,
        public string $createdAt,
    ) {}

    /**
     * Hydrate from a raw PDO FETCH_ASSOC row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int)$row['id'] : null,
            originalPath: (string)$row['original_path'],
            processedPath: isset($row['processed_path']) ? (string)$row['processed_path'] : null,
            status: AudioProcessingStatus::from((string)$row['status']),
            operation: AudioProcessingOperation::from((string)$row['operation']),
            durationBefore: isset($row['duration_before']) ? (float)$row['duration_before'] : null,
            durationAfter: isset($row['duration_after']) ? (float)$row['duration_after'] : null,
            sizeBefore: isset($row['size_before']) ? (int)$row['size_before'] : null,
            sizeAfter: isset($row['size_after']) ? (int)$row['size_after'] : null,
            errorMessage: isset($row['error_message']) ? (string)$row['error_message'] : null,
            processedAt: isset($row['processed_at']) ? (string)$row['processed_at'] : null,
            createdAt: (string)$row['created_at'],
        );
    }
}
