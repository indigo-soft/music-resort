# ADR-0005: Parallel Processing via PHP Child Processes

## Status

Accepted — 2026-05-09

## Context

The `music:resort` command moves files by reading their metadata and placing them into artist folders. For large
collections (tens of thousands of files), sequential processing is too slow. The solution must work on Linux, macOS,
and Windows without native thread support, and must integrate with the existing Symfony Console and Process stack.

## Considered Alternatives

### pthreads / parallel (PHP extensions)

**Pros:**
- True shared-memory threading
- Lower overhead per thread than a new process

**Cons:**
- `pthreads` requires a thread-safe PHP build (ZTS) — not the default on most systems
- `parallel` extension is not bundled; requires manual installation
- Both extensions are incompatible with many common extensions (XDebug, OPcache in some modes)
- Adds a system dependency that contradicts the project's zero-system-dependency goal for PHP

### ReactPHP (event loop + async I/O)

**Pros:**
- Pure PHP; no extensions required
- Fine-grained concurrency model

**Cons:**
- Requires restructuring all I/O operations as non-blocking callbacks — a large rewrite
- getID3 and Symfony Filesystem are synchronous; wrapping them is non-trivial
- Adds `react/*` packages to the dependency tree

### Process Pool (Gearman / RabbitMQ)

**Pros:**
- Production-grade job queue

**Cons:**
- Requires running a separate broker service — extreme overhead for a CLI tool
- No value for a one-shot batch operation

### PHP Child Processes via Symfony Process (chosen)

Each worker is a separate `php bin/console music:resort` invocation. The parent splits the file list into N batches,
writes each batch to a temp JSON file, spawns N child processes, waits for all to finish, and aggregates JSON results.

## Decision

Implement parallel processing as **N child PHP processes** spawned via `Symfony\Component\Process\Process`.

The coordinator (`ResortMp3Command` with `--concurrency=N`) and worker (`--worker-batch` / `--result-json` internal
options) run the same `music:resort` command entry point. Result aggregation uses temp JSON files in `sys_get_temp_dir()`.

## Rationale

**No new dependencies.** `symfony/process` is already in the stack for FFmpeg calls (ADR-0001). Child-process
parallelism reuses it without adding anything.

**Cross-platform.** PHP child processes work identically on Linux, macOS, and Windows — unlike `pcntl_fork()` which
is unavailable on Windows.

**Isolation.** Each worker has its own memory space. A crash or exception in one worker does not corrupt the parent or
other workers. Exit codes signal success/failure cleanly.

**Simple coordination protocol.** Batch file (input JSON array of paths) + result file (output JSON with counts) is
the entire IPC contract. No sockets, shared memory, or message queues.

**Transparent to the user.** `--concurrency=1` (default) runs sequentially with no spawning overhead. Workers use the
same output channel as the parent, so progress is visible.

## Worker Protocol

```
Parent                                    Worker (child process)
  |                                           |
  |-- write batch_N.json (file paths) ------> |
  |-- spawn: php bin/console music:resort     |
  |       --worker-batch=batch_N.json         |
  |       --result-json=result_N.json ------> |
  |                                           |-- process files
  |                                           |-- write result_N.json
  |<-- process->wait() ---------------------- |
  |-- read result_N.json                      |
  |-- aggregate processed/errors counts       |
  |-- unlink temp files                       |
```

## Consequences

### Positive

- Linear throughput scaling up to N workers (bounded by disk I/O, not CPU)
- Works on all three target platforms without extensions
- Failure isolation: one bad batch does not abort others
- Temp files are always cleaned up in the cleanup step

### Negative

- Process startup overhead (~100–200 ms per worker) makes `--concurrency` only worthwhile for large collections
- `bin/console` must remain a stable entry point; its location is hardcoded via `realpath(dirname(__DIR__, 2))`
- Internal options (`--worker-batch`, `--result-json`) must not be used by end users; they are not validated
  against misuse

### Neutral

- The same concurrency pattern can be applied to future commands (`audio:process`) without architectural changes
- Worker batch size is `ceil(total / concurrency)` — uneven splits are handled naturally by the last batch being
  smaller
