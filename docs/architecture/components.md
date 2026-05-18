# Architecture Components — music-resort

## Component Map

The application has one process boundary: the CLI invocation of `bin/console`.
There are no separate services, daemons, or network components.
Internally, `music:resort --concurrency=N` spawns child PHP processes — but these
are coordination workers, not independent components.

---

## Commands (thin layer)

All commands live in `src/Command/`. Each is a thin adapter: it reads CLI input via
`ConsoleCommandService` and delegates all business logic to a matching service.

### RunAllCommand — `music:all`

**Responsibility:** Orchestrates the full pipeline in a fixed order.

Pipeline order:
1. `music:resort` — only if `destination` argument is provided
2. `music:fix-extensions`
3. `music:deduplicate`
4. `music:clean`
5. `music:clean-empty-dirs`

**Interfaces:**
- Receives: `source` (required), `destination` (optional), `--dry-run`, `--concurrency=N`
- Invokes sibling commands via `Application::find($name)->run(new ArrayInput(...))`
- Propagates `--dry-run` to all sub-commands automatically

**External dependencies:** none

---

### ResortMp3Command — `music:resort`

**Responsibility:** Sorts audio files into artist-named subdirectories.

**Interfaces:**
- Receives: `source`, `destination`, `--dry-run`, `--concurrency=N`
- Delegates to: `Mp3ResortService` → `MusicMetadataService`, `FileResortService`
- With `--concurrency=N > 1`: spawns child PHP processes via `ResortMp3Helper`

**External dependencies:** none (child processes are the same PHP binary)

---

### DeduplicateMp3Command — `music:deduplicate`

**Responsibility:** Detects and removes duplicate audio files based on acoustic fingerprint
or file hash (see ADR for strategy).

**Interfaces:**
- Receives: `source`, `--dry-run`
- Delegates to: `Mp3DeduplicateService` → `MusicMetadataService`

---

### FixExtensionsCommand — `music:fix-extensions`

**Responsibility:** Corrects file extensions that don't match the actual audio format
stored in the file's metadata.

**Interfaces:**
- Receives: `source`, `--dry-run`
- Delegates to: `FixExtensionsService` → `MusicMetadataService`

---

### CleanMp3Command — `music:clean`

**Responsibility:** Removes corrupted or invalid audio files that cannot be read.

**Interfaces:**
- Receives: `source`, `--dry-run`
- Delegates to: `Mp3CleanService`

---

### CleanEmptyDirsCommand — `music:clean-empty-dirs`

**Responsibility:** Removes empty directories left behind after other commands.

**Interfaces:**
- Receives: `source`, `--dry-run`
- Delegates to: `EmptyDirsCleanupService`

---

## Services (business logic layer)

All services live in `src/Service/`. All dependencies are injected via constructor.
Services do not instantiate other services internally.

### ConsoleCommandService

**Responsibility:** Shared CLI input helper — centralises reading of `source`, `destination`,
and `--dry-run` across all commands.

`isDryRun()` combines the `--dry-run` CLI flag AND the `app.debug` config value.
Always use this method — never read `--dry-run` directly from `InputInterface` in services.

**Used by:** every command

---

### ConfigService

**Responsibility:** Loads and provides typed access to configuration.

- Reads `.env` using a custom parser (no dotenv library)
- Reads `config/app.php` (schema-driven: env-var name, type, default)
- Access via `ConfigService::get('app.debug')` (dot-notation)
- Static singleton — initialised once in `bin/console`

**Note:** This is a legacy static singleton. New services must not call it directly —
inject the values they need via constructor.

---

### LocalizationService

**Responsibility:** Loads and caches translation files.

- Translation files: `lang/<locale>/console.php` (nested PHP arrays)
- Accessed via global helper `__('key.subkey', ['placeholder' => $value])`
- Static singleton — initialised once in `bin/console`

**Note:** Same legacy exception as `ConfigService`. New services must not call it.

---

### MusicMetadataService

**Responsibility:** Single integration point for the `getid3` library.

- Reads audio metadata tags: `artist`, `albumartist`, `band`, `performer` (in that order)
- Splits multi-artist values by: `; , / & feat. ft. featuring`
- Returns the first artist from the list

**Rule:** This is the only class allowed to use `getid3` directly. Do not call `getid3` elsewhere.

---

### Mp3ResortService

**Responsibility:** Resort pipeline — file discovery and move orchestration.

- Discovers files: `*.mp3`, `*.flac`, `*.m4a` (excludes `@eaDir`, `.AppleDouble`, `.AppleDB`)
- Coordinates `MusicMetadataService` (get artist) and `FileResortService` (move file)
- For `--concurrency=N > 1`: splits files into batches and delegates to `ResortMp3Helper`

---

### FileResortService

**Responsibility:** File system operations for the resort step.

- `sanitizeFolderName()` — strips invalid characters, limits length to 100 characters
- Handles move/rename collision (auto-renames on conflict)
- Creates destination artist folders

---

### Mp3DeduplicateService

**Responsibility:** Deduplication logic — detects and removes duplicate audio files.

Uses `MusicMetadataService` for metadata comparison.

---

### FixExtensionsService

**Responsibility:** Extension correction — renames files whose extension doesn't match
the actual audio format in their metadata.

Uses `MusicMetadataService` to read the actual format.

---

### Mp3CleanService

**Responsibility:** Identifies and removes corrupted or unreadable audio files.

---

### EmptyDirsCleanupService

**Responsibility:** Traverses the source directory and removes empty subdirectories.

---

## Parallel Workers (`ResortMp3Helper`)

Lives in `src/Helpers/ResortMp3Helper.php`. When `--concurrency=N > 1` is passed to
`music:resort`, this helper:

1. Splits the file list into N batches
2. Writes each batch to a temp file (`--worker-batch`)
3. Spawns N child PHP processes via `symfony/process`, each running `bin/console music:resort`
   with the internal `--worker-batch=<file>` and `--result-json=<file>` options
4. Collects results and merges output

**⚠️ `--worker-batch` and `--result-json` are internal options.** Do not use them directly.
See [ADR-0005](../adr/ADR-0005-parallel-processing-child-processes.md).
