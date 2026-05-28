# ADR-0006: SQLite via Raw PDO as Persistence Layer

## Status

Accepted

## Date

2026-05-28

## Context

The `audio:process` command (ADR-0001) must log the result of every processed file —
status, operation, durations, file sizes, error messages — so the user can review the
outcome before running `audio:cleanup-originals`. This data must survive across process
restarts and be queryable by status.

The project already follows a minimal-dependency philosophy (ADR-0002, ADR-0004):
no ORM container, no HTTP framework, manual DI in `bin/console`.

## Alternatives Considered

### Doctrine DBAL
- Pros: query builder, schema manager, familiar in Symfony projects.
- Cons: ~40 files; adds a significant dependency for 1–2 tables in a CLI tool.

### Cycle ORM / Eloquent standalone
- Full ORM with entity mapping and relationships.
- Cons: heavy, designed for web apps, unnecessary complexity for a CLI with 1–2 tables.

### CSV / JSON file
- Pros: zero dependencies.
- Cons: no atomic updates, no concurrent-write safety, not queryable by status.

## Decision

Use **raw PDO** with a **Repository pattern**. No new Composer packages.

- `\PDO` is instantiated once in `bin/console` with `ERRMODE_EXCEPTION` and WAL journal mode.
- `DatabaseMigrationService` applies versioned DDL migrations on every boot (`CREATE TABLE IF NOT EXISTS`).
  Migrations are numbered; each is applied once and recorded in `schema_migrations`.
- `AudioProcessingRepository` encapsulates all SQL. No SQL leaks into service classes.
- All repository classes receive `\PDO` via constructor — ADR-0002 compliant.
- DB path is configured via `DB_PATH` env variable (default: `./db/music.sqlite`).
  Relative paths are resolved from the project root in `bin/console`.

## Consequences

### Positive
- Zero new Composer dependencies — PDO ships with PHP.
- Every repository is trivially testable: inject `new PDO('sqlite::memory:')` in tests.
- Full SQL control without abstraction limits.
- WAL mode enables safe concurrent reads from worker processes (ADR-0005).

### Negative
- Schema migrations are hand-written SQL — no auto-generation.
- No query builder: complex queries require careful string construction.

### Neutral
- If the number of tables or query complexity grows significantly, introducing Doctrine DBAL
  can be done via a separate ADR without breaking the Repository interface.
- The `db/` directory is already present in the repository (gitignored content).
