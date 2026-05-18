# Architecture Overview — music-resort

## Purpose

music-resort solves the problem of managing large, disorganised local music libraries.
It automatically sorts audio files (mp3, flac, m4a) into artist folders, removes duplicates,
fixes incorrect file extensions based on metadata, and cleans corrupted or empty directories —
all from the command line, with no internet connection or external services required.

## Users & Consumers

- **Primary user:** a single developer / music library owner running commands locally on their machine
- **Invocation:** manual CLI calls, or scripted via shell aliases / cron

The tool is not a service — it has no API, no daemon, no web interface.

## System Boundaries

```
┌─────────────────────────────────────────────────────┐
│                   music-resort                      │
│                                                     │
│  Input:  local filesystem (mp3 / flac / m4a files)  │
│  Config: .env, config/app.php                       │
│  i18n:   lang/en/, lang/uk/                         │
│                                                     │
│  Output: sorted / deduplicated / cleaned files      │
│          on the same local filesystem               │
└─────────────────────────────────────────────────────┘

No network I/O. No database. No external services.
```

**Input:**
- Source directory path (required for all commands)
- Destination directory path (optional; only for `music:resort`)
- CLI flags: `--dry-run`, `--concurrency=N`
- Environment: `DEBUG` (force dry-run), `DEFAULT_LANG` (ui locale)

**Output:**
- Files moved, renamed, or deleted on the local filesystem
- Console output (progress bars, success/warning/error messages)
- In `--dry-run` mode: console output only, no filesystem changes

## Key Constraints

| Constraint | Detail |
|------------|--------|
| No external dependencies | No database, no API, no cloud storage |
| Dry-run by default locally | `DEBUG=true` in `.env` forces dry-run globally |
| Cross-platform | Works on Linux, macOS, WSL, and Windows (via `music.bat`) |
| Parallel processing | `--concurrency=N` spawns PHP child processes (not threads) |
| Single metadata library | `getid3` only — no alternatives |
| PHP 8.5+ | Strict types required in all files |

## Architecture Diagram

```
User (CLI)
    │
    ▼
bin/console ──── bootstrap: load .env → init config → set locale → register commands
    │
    ├── music:all          ← orchestrator (RunAllCommand)
    │       │
    │       ├── music:resort        ─┐
    │       ├── music:fix-extensions │  invoked via Application::find()->run()
    │       ├── music:deduplicate    │
    │       ├── music:clean         │
    │       └── music:clean-empty-dirs ─┘
    │
    ├── music:resort       ← ResortMp3Command
    │       └── Mp3ResortService ── MusicMetadataService (getid3)
    │                           └── FileResortService
    │                           └── [child PHP processes via --concurrency]
    │
    ├── music:deduplicate  ← DeduplicateMp3Command
    │       └── Mp3DeduplicateService ── MusicMetadataService
    │
    ├── music:fix-extensions ← FixExtensionsCommand
    │       └── FixExtensionsService ── MusicMetadataService
    │
    ├── music:clean        ← CleanMp3Command
    │       └── Mp3CleanService
    │
    └── music:clean-empty-dirs ← CleanEmptyDirsCommand
            └── EmptyDirsCleanupService

Shared infrastructure (injected via bin/console):
    ConsoleCommandService  — CLI input helpers (source, dest, dry-run)
    ConfigService          — .env + config/app.php loader (static singleton)
    LocalizationService    — translation cache (static singleton)
```
