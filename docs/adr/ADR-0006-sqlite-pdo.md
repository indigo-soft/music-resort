# ADR-0006: MariaDB via Raw PDO as Persistence Layer

## Status

Accepted (supersedes SQLite via Raw PDO, 2026-05-28)

## Date

2026-06-03

## Context

The project initially used SQLite for persistence (API cache, logs, metadata inventory).
SQLite caused a persistent `SQLITE_BUSY` error in IDE tools (DataGrip, DBeaver) regardless
of journal mode (WAL, DELETE) or WAL checkpoint strategies. The root cause was incompatibility
between SQLite file locking and the JDBC/native SQLite drivers used by IDEs on WSL2.

The project follows a minimal-dependency philosophy (ADR-0002, ADR-0004): no ORM,
manual DI in `bin/console`, raw PDO with a Repository pattern.

## Decision

Switch from SQLite to **MariaDB** via raw PDO. No ORM. Repository pattern unchanged.

- DSN: `mysql:host=...;port=...;dbname=...;charset=utf8mb4`
- PDO options: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `MYSQL_ATTR_INIT_COMMAND` for charset
- Connection credentials: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` in `.env`
- `DatabaseMigrationService` applies versioned `.sql` files from `db/migrations/`
- All repositories receive `\PDO` via constructor — ADR-0002 compliant

## Consequences

### Positive
- Full IDE compatibility (DataGrip, DBeaver, TablePlus)
- No file-locking issues
- Zero new Composer packages — `ext-pdo_mysql` ships with PHP
- utf8mb4 collation handles all Unicode music metadata correctly

### Negative
- Requires a running MariaDB server (local or remote)
- Migration SQL syntax differs from SQLite (AUTO_INCREMENT, CURRENT_TIMESTAMP, ON DUPLICATE KEY UPDATE)
- `db/database/` directory and SQLite file removed from the project

### Neutral
- Repository interfaces unchanged — only SQL internals updated
- `db/migrations/*.sql` files remain the single source of truth for schema
- If MariaDB server is unavailable, bootstrap exits with a clear error message
