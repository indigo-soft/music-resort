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
 *   NNN_description.sql (e.g., 001_audio_processing.sql)
 *
 * Applied migrations are recorded in the `migrations` table (filename as PK).
 * Each file is applied exactly once. Safe to call on every boot.
 *
 * Adding a new migration: create the next numbered .sql file in db/migrations/.
 * No changes to this class are required.
 *
 * Usage (bin/console):
 *   $service = new DatabaseMigrationService($pdo, $projectRoot . '/db/migrations');
 *   $service->migrate();
 */
final readonly class DatabaseMigrationService
{
    public function __construct(
        private PDO $pdo,
        private string $migrationsPath,
    ) {}

    /**
     * Apply all pending migrations.
     *
     * @return list<string> filenames of newly applied migrations
     */
    public function migrate(): array
    {
        $this->createMigrationsTable();

        $applied = $this->getAppliedFilenames();
        $pending = $this->getPendingFiles($applied);
        $result  = [];

        foreach ($pending as $filename => $filepath) {
            $this->applyFile($filename, $filepath);
            $result[] = $filename;
        }

        return $result;
    }

    /**
     * Return filenames of all applied migrations.
     *
     * @return list<string>
     */
    public function getApplied(): array
    {
        $this->createMigrationsTable();

        return $this->getAppliedFilenames();
    }

    /**
     * Return filenames of all pending (not yet applied) migrations.
     *
     * @return list<string>
     */
    public function getPending(): array
    {
        $this->createMigrationsTable();

        return array_keys($this->getPendingFiles($this->getAppliedFilenames()));
    }

    /**
     * Verify that write operations are possible on the database.
     *
     * @throws RuntimeException if the database is not writable
     */
    public function assertWritable(): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->rollBack();
        } catch (Throwable $e) {
            throw new RuntimeException('Database is not writable: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Drop all tables except the ones listed in $preserveTables.
     * Disables foreign key checks during the operation.
     *
     * @param list<string> $preserveTables table names to keep
     * @return int number of tables dropped
     */
    public function dropAllExcept(array $preserveTables): int
    {
        $stmt   = $this->pdo->query(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'",
        );
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $toDrop = array_filter(
            $tables,
            static fn(string $name): bool => !in_array($name, $preserveTables, strict: true),
        );

        if ($toDrop === []) {
            return 0;
        }

        $this->pdo->exec('SET foreign_key_checks = 0');

        try {
            foreach ($toDrop as $table) {
                $this->pdo->exec('DROP TABLE IF EXISTS ' . $table);
            }
        } finally {
            $this->pdo->exec('SET foreign_key_checks = 1');
        }

        return count($toDrop);
    }

    /**
     * Delete all records from the migration table (does not drop the table).
     * Used by migrate:refresh to allow re-applying all migrations.
     */
    public function clearMigrations(): void
    {
        /* @noinspection SqlWithoutWhere */
        $this->pdo->exec('DELETE FROM migrations');
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Scan the migrations directory and return pending files as
     * [ filename => filepath ] sorted ascending, excluding already applied.
     *
     * @param list<string> $applied
     * @return array<string, string>
     */
    private function getPendingFiles(array $applied): array
    {
        $discovered = $this->discoverMigrations();

        return array_filter($discovered, static function($filename) use ($applied) {
            return !in_array($filename, $applied, strict: true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Scan the migrations directory and return [ filename => filepath ] sorted ascending.
     *
     * @return array<string, string>
     */
    private function discoverMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            throw new RuntimeException('Migrations directory not found: ' . $this->migrationsPath, );
        }

        $files = glob($this->migrationsPath . '/*.sql');

        if ($files === false || $files === []) {
            return [];
        }

        sort($files);

        $migrations = [];

        foreach ($files as $filepath) {
            $filename = basename($filepath);

            if (!preg_match('/^\d+_/', $filename)) {
                throw new RuntimeException('Migration filename must start with a numeric prefix (e.g. 001_name.sql): ' . $filename, );
            }

            if (isset($migrations[$filename])) {
                throw new RuntimeException('Duplicate migration filename: ' . $filename, );
            }

            $migrations[$filename] = $filepath;
        }

        return $migrations;
    }

    /**
     * Parse and execute a single .sql file inside a transaction.
     * Records the filename in the migration table on success.
     *
     * @param string $filename
     * @param string $filepath
     */
    private function applyFile(string $filename, string $filepath): void
    {
        $sql = file_get_contents($filepath);

        if ($sql === false) {
            throw new RuntimeException('Cannot read migration file: ' . $filepath);
        }

        $statements = explode(';', $sql)
                |> (static fn($x) => array_map('trim', $x))
                |> (static fn($x) => array_filter($x, static fn(string $s): bool => $s !== '', ));

        // MariaDB DDL statements (CREATE TABLE, CREATE INDEX, etc.) implicitly commit
        // any active transaction, making transactional DDL impossible. Running without
        // an explicit transaction is equivalent in terms of atomicity guarantees.
        try {
            foreach ($statements as $statement) {
                $this->pdo->exec($statement);
            }

            $this->recordMigration($filename);
        } catch (Throwable $e) {
            throw new RuntimeException('Migration failed: ' . $filename . ' — ' . $e->getMessage(), previous: $e, );
        }
    }

    private function createMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                filename   VARCHAR(255) NOT NULL,
                applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    /**
     * @return list<string>
     */
    private function getAppliedFilenames(): array
    {
        return $this->pdo->query('SELECT migrations.filename FROM migrations ORDER BY filename')->fetchAll(PDO::FETCH_COLUMN);
    }

    private function recordMigration(string $filename): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)');
        $stmt->execute(['filename' => $filename]);
    }
}
