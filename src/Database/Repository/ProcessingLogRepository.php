<?php

declare(strict_types=1);

namespace MusicResort\Database\Repository;

use MusicResort\Enum\LogLevel;
use PDO;

/**
 * Persistence layer for the processing_log table.
 *
 * This repository is the write side of the structured log. It is injected into
 * DatabaseLoggerService, which implements LoggerInterface and replaces
 * FileLoggerService as the single log sink for all commands.
 *
 * Each command invocation generates a run_id (UUIDv4) that groups all log
 * entries from that run, enabling per-run filtering and summary queries.
 *
 * DI: receive \PDO via constructor (ADR-0002). Instantiate only in bin/console.
 */
final class ProcessingLogRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * Append a single log entry.
     *
     * @param string $command CLI command name, e.g. "mp3:resort"
     * @param LogLevel $level Severity level
     * @param string $message Human-readable message
     * @param string $runId UUIDv4 for the current command invocation
     * @param array<string, mixed> $context Optional key/value pairs stored as JSON
     */
    public function insert(
        string $command,
        LogLevel $level,
        string $message,
        string $runId,
        array $context = [],
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO processing_log (command, level, message, context, run_id)
             VALUES (:command, :level, :message, :context, :run_id)',
        );

        $stmt->execute([
            ':command' => $command,
            ':level'   => $level->value,
            ':message' => $message,
            ':context' => $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
            ':run_id'  => $runId,
        ]);
    }

    /**
     * Return all entries for a given run, oldest first.
     *
     * @param string $runId
     * @return list<array<string, mixed>>
     */
    public function findByRunId(string $runId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM processing_log
             WHERE run_id = :run_id
             ORDER BY id ASC',
        );

        $stmt->execute([':run_id' => $runId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return the most recent N entries across all commands, newest first.
     *
     * @param int $limit Max rows to return (default 100)
     * @return list<array<string, mixed>>
     */
    public function findRecent(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM processing_log
             ORDER BY id DESC
             LIMIT :limit',
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all entries at or above a given level for the given command, newest first.
     *
     * @param string $command
     * @param LogLevel $minLevel
     * @param int $limit
     * @return list<array<string, mixed>>
     */
    public function findByCommandAndLevel(
        string $command,
        LogLevel $minLevel,
        int $limit = 200,
    ): array {
        $levels = match ($minLevel) {
            LogLevel::Debug   => ['debug', 'info', 'warning', 'error'],
            LogLevel::Info    => ['info', 'warning', 'error'],
            LogLevel::Warning => ['warning', 'error'],
            LogLevel::Error   => ['error'],
        };

        $placeholders = implode(',', array_fill(0, count($levels), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT * FROM processing_log
             WHERE command = ?
               AND level IN ({$placeholders})
             ORDER BY id DESC
             LIMIT ?",
        );

        $stmt->execute([$command, ...$levels, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count entries grouped by level for a specific run.
     *
     * @param string $runId
     * @return array<string, int> e.g. ['info' => 42, 'error' => 2]
     */
    public function countByLevelForRun(string $runId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT level, COUNT(*) as cnt
             FROM processing_log
             WHERE run_id = :run_id
             GROUP BY level',
        );

        $stmt->execute([':run_id' => $runId]);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(string)$row['level']] = (int)$row['cnt'];
        }

        return $result;
    }

    /**
     * Delete all entries older than the given number of days.
     * Useful for periodic housekeeping to keep the log table lean.
     *
     * @param int $days
     */
    public function deleteOlderThan(int $days): int
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM processing_log
             WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)',
        );

        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
