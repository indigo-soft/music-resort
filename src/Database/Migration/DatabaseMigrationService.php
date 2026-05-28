<?php

declare(strict_types=1);

namespace MusicResort\Database\Migration;

use PDO;
use Throwable;

/**
 * Applies versioned DDL migrations to the SQLite database.
 *
 * Migrations are numbered sequentially. Each version is applied exactly once
 * and recorded in the schema_migrations table. Safe to call on every boot.
 *
 * Usage (bin/console):
 *   $migrationService = new DatabaseMigrationService($pdo);
 *   $migrationService->migrate();
 */
final class DatabaseMigrationService
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function migrate(): void
    {
        $this->createMigrationsTable();

        $applied = $this->getAppliedVersions();

        foreach ($this->getMigrations() as $version => $statements) {
            if (in_array($version, $applied, strict: true)) {
                continue;
            }

            $this->pdo->beginTransaction();

            try {
                foreach ($statements as $sql) {
                    $this->pdo->exec($sql);
                }
                $this->recordVersion($version);
                $this->pdo->commit();
            } catch (Throwable $e) {
                $this->pdo->rollBack();

                throw $e;
            }
        }
    }

    /**
     * Returns all migrations as [ version => string[] $statements ].
     *
     * Add new migrations at the end only — never modify existing ones.
     *
     * @return array<int, string[]>
     */
    private function getMigrations(): array
    {
        return [
            1 => $this->migration001AudioProcessing(),
        ];
    }

    // ------------------------------------------------------------------
    // Migration definitions
    // ------------------------------------------------------------------

    /**
     * @return string[]
     */
    private function migration001AudioProcessing(): array
    {
        return [
            'CREATE TABLE IF NOT EXISTS audio_processing (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                original_path  TEXT    NOT NULL,
                processed_path TEXT,
                status         TEXT    NOT NULL DEFAULT \'pending\',
                operation      TEXT    NOT NULL,
                duration_before REAL,
                duration_after  REAL,
                size_before    INTEGER,
                size_after     INTEGER,
                error_message  TEXT,
                processed_at   TEXT,
                created_at     TEXT    NOT NULL DEFAULT (datetime(\'now\'))
            )',
            'CREATE INDEX IF NOT EXISTS idx_ap_status
                ON audio_processing (status)',
            'CREATE INDEX IF NOT EXISTS idx_ap_original_path
                ON audio_processing (original_path)',
        ];
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function createMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version    INTEGER PRIMARY KEY,
                applied_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
            )',
        );
    }

    /**
     * @return int[]
     */
    private function getAppliedVersions(): array
    {
        $stmt = $this->pdo->query('SELECT version FROM schema_migrations ORDER BY version');

        /** @var list<int> $versions */
        $versions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map(intval(...), $versions);
    }

    private function recordVersion(int $version): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:v)');
        $stmt->execute([':v' => $version]);
    }
}
