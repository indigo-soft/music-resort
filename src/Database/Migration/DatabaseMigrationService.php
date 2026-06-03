<?php

declare(strict_types=1);

namespace MusicResort\Database\Migration;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Applies versioned SQL migrations from db/migrations/*.sql to the SQLite database.
 *
 * Migration files must follow the naming convention:
 *   NNN_description.sql  (e.g. 001_audio_processing.sql)
 *
 * Each file is applied exactly once and recorded in schema_migrations.
 * Safe to call on every boot — already-applied versions are skipped.
 *
 * Adding a new migration: create the next numbered .sql file in db/migrations/.
 * No changes to this class are required.
 *
 * Usage (bin/console):
 *   $migrationService = new DatabaseMigrationService($pdo, $projectRoot . '/db/migrations');
 *   $migrationService->migrate();
 */
final class DatabaseMigrationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {}

    public function migrate(): void
    {
        $this->createMigrationsTable();

        $applied = $this->getAppliedVersions();

        foreach ($this->discoverMigrations() as $version => $file) {
            if (in_array($version, $applied, strict: true)) {
                continue;
            }

            $this->applyFile($version, $file);
        }
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Scan the migrations directory and return [ version => filepath ] sorted ascending.
     *
     * @return array<int, string>
     */
    private function discoverMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            throw new RuntimeException(sprintf('Migrations directory not found: %s', $this->migrationsPath), );
        }

        $files = glob($this->migrationsPath . '/*.sql');

        if ($files === false || $files === []) {
            return [];
        }

        sort($files);

        $migrations = [];

        foreach ($files as $file) {
            $basename = basename($file);

            if (!preg_match('/^(\d+)_/', $basename, $matches)) {
                throw new RuntimeException(sprintf('Migration filename must start with a numeric prefix (e.g. 001_name.sql): %s', $basename, ), );
            }

            $version = (int)$matches[1];

            if (isset($migrations[$version])) {
                throw new RuntimeException(sprintf('Duplicate migration version %d: %s', $version, $basename), );
            }

            $migrations[$version] = $file;
        }

        return $migrations;
    }

    /**
     * Parse a .sql file into individual statements and execute them in a transaction.
     *
     * @param int $version
     * @param string $file
     */
    private function applyFile(int $version, string $file): void
    {
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException(sprintf('Cannot read migration file: %s', $file));
        }

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            static fn(string $s): bool => $s !== '',
        );

        $this->pdo->beginTransaction();

        try {
            foreach ($statements as $statement) {
                $this->pdo->exec($statement);
            }

            $this->recordVersion($version);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    private function createMigrationsTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                version    INTEGER PRIMARY KEY,
                applied_at TEXT    NOT NULL DEFAULT (datetime('now'))
            )",
        );
    }

    /**
     * @return int[]
     */
    private function getAppliedVersions(): array
    {
        $stmt = $this->pdo->query('SELECT version FROM schema_migrations ORDER BY version');

        return array_map(intval(...), $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function recordVersion(int $version): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:v)');
        $stmt->execute([':v' => $version]);
    }
}
