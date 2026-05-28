# ADR-0007: File-Based Logging via Internal FileLoggerService

## Status

Accepted

## Date

2026-05-28

## Context

Commands and services need structured runtime logging for debugging, audit trails, and
surfacing processing errors to the user without cluttering the console output.

The project follows a minimal-dependency philosophy (ADR-0002, ADR-0004).
`psr/log` is available as a transitive dependency via Symfony Console, but relying on
transitive dependencies is fragile. Monolog is the de-facto PHP standard but adds ~30 files
of handlers, formatters, and processors that are unnecessary for a single-file rotating log.

## Alternatives Considered

### Monolog
- Pros: industry standard; handlers for files, syslog, Slack, etc.; log rotation built in.
- Cons: adds `monolog/monolog` as a direct dependency; significant surface area for a CLI
  tool that only needs timestamped lines in a single file.

### symfony/monolog-bridge + Monolog
- Requires both `monolog/monolog` and the bridge — two new packages.

### Raw `error_log()` / `file_put_contents` in each service
- No structure, no log levels, no context, SQL injection risk if paths are logged carelessly.

### psr/log + custom handler (without Monolog)
- Could use the PSR-3 `LoggerInterface` directly, but psr/log being a transitive dep
  means it could disappear if Symfony updates its dependency tree.

## Decision

Define a **project-local** `MusicResort\Logger\LoggerInterface` with four methods
(`info`, `warning`, `error`, `debug`) and implement it as `FileLoggerService`.

Line format:
```
[2026-05-28 14:05:33] [INFO] Message processed {"file":"track.mp3","size":4096}
```

- `FileLoggerService` receives `$logPath` via constructor — ADR-0002 compliant.
- Log directory is created automatically on first write (`mkdir` with `recursive: true`).
- `FILE_APPEND | LOCK_EX` prevents interleaving from parallel worker processes (ADR-0005).
- Log path is configured via `LOG_PATH` env variable (default: `./storage/logs/app.log`).
- Relative paths resolved from project root in `bin/console`.

## Consequences

### Positive
- Zero new Composer dependencies.
- `LoggerInterface` is a 4-method contract — trivially mockable in unit tests.
- No dependency on PSR-3 transitive package.

### Negative
- No log rotation — log file grows indefinitely. Manual rotation or logrotate must be
  configured externally if the log grows large.
- No multiple handlers (e.g., simultaneously write to file and emit to console).

### Neutral
- If Monolog is introduced in the future (e.g., for Slack alerts or log rotation),
  `FileLoggerService` can be replaced by a Monolog adapter that implements
  `MusicResort\Logger\LoggerInterface` without changing any service code.
