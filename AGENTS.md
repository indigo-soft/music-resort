# AGENTS.md — Music Resort Tool

## Project Overview

PHP 8.5+ CLI tool (Symfony Console) for sorting, deduplicating, fixing, and cleaning music files (mp3/flac/m4a).
Namespace: `MusicResort\` → `src/`. Entry point: `bin/console`.

## Developer Workflows

```bash
composer install            # install all dependencies
composer test               # run Pest tests (alias: pest -d)
composer cs                 # check code style (dry-run)
composer fix                # auto-fix code style
composer stan               # static analysis (PHPStan level configured in phpstan.neon.dist)
composer lint               # fix + stan combined
./music list                # optional short wrapper for bin/console (Linux/macOS/WSL)
music.bat list              # optional short wrapper for bin/console (Windows cmd)
```

No tests exist yet — `tests/` directory is absent. The `composer test` script will fail until tests are added.

## Architecture

### Command → Service pattern

Each command (`src/Command/`) is thin: it reads input/options via `ConsoleCommandService` and delegates all logic to a
matching service (`src/Service/`).

- `ConsoleCommandService` — centralises `source`, `destination`, `dry-run` extraction from CLI input; also reads
  `app.debug` from config, so `DEBUG=true` in `.env` globally forces dry-run.
- `ConfigService::init()` — static, called once in `bin/console`. Reads `.env` (custom parser, no dotenv library), then
  `config/app.php` (schema-driven with env-name, type, default).
- `ConfigService::get('app.debug')` / `ConfigService::get('app.default_lang')` — dot-notation access.

- `RunAllCommand` (`music:all`) is an orchestrator command: it sequentially invokes `music:resort` (only when
  `destination` is provided), then `music:fix-extensions`, `music:deduplicate`, `music:clean`,
  `music:clean-empty-dirs` via `Application::find(...)->run(new ArrayInput(...))`.

### Parallel workers (music:resort only)

`--concurrency=N` makes `ResortMp3Command` split files into batches and spawn child PHP processes (via
`symfony/process`) that re-invoke `bin/console music:resort --worker-batch=<file> --result-json=<file>`. Options
`--worker-batch` and `--result-json` are internal worker-coordination options used by parent/child process calls and
result aggregation through temp JSON files.

### Localization

Global helper `__('console.key.subkey', ['placeholder' => $value])` → `LocalizationService::get()`.
Translation files: `lang/<locale>/console.php` (nested PHP arrays, dot-notation keys).
Placeholders use `:name` syntax (Laravel-style). Locale set at boot via `DEFAULT_LANG` env var.

## Key Conventions

- All files: `declare(strict_types=1)`.
- Services are `final` classes with only `static` state where global singleton behaviour is needed (`ConfigService`,
  `LocalizationService`).
- `dry-run` logic: always check `ConsoleCommandService::isDryRun()` — it combines `--dry-run` flag AND `app.debug`
  config. Never check the option directly in service classes.
- Translation keys for "would do X" (dry-run) live under `note.*`, actual actions under `info.*`.
- Folder name sanitization lives in `FileResortService::sanitizeFolderName()` (max folder name length = 100 chars);
  artist extraction from tags + first-artist split is handled by `MusicMetadataService` and `Mp3ResortService`.
- Resort file discovery in `Mp3ResortService` intentionally includes `*.mp3`, `*.flac`, `*.m4a` and excludes
  `@eaDir`, `.AppleDouble`, `.AppleDB`.
- `getid3` (`james-heinrich/getid3`) is the sole metadata library. Tags checked in order: `artist`, `albumartist`,
  `band`, `performer`. Multi-artist separators: `;`, `,`, `/`, `&`, `feat.`, `ft.`, `featuring`.

## Adding a New Command

1. Create `src/Command/MyCommand.php` extending `Symfony\Component\Console\Command\Command` with `#[AsCommand]`.
2. Create `src/Service/MyService.php` with the business logic.
3. Register in `bin/console`: `$application->add(new MyCommand())`.
4. Add translation keys to `lang/en/console.php` (and `lang/uk/console.php`).
5. Use `ConsoleCommandService` to read `source`, `destination`, `dry-run`.

## Environment Variables (`.env` in project root)

| Variable       | Type   | Default | Purpose                  |
|----------------|--------|---------|--------------------------|
| `DEBUG`        | bool   | `false` | Force dry-run globally   |
| `DEFAULT_LANG` | string | `en`    | UI locale (`en` or `uk`) |

## Key Files

- `bin/console` — bootstrap: init config → set locale → register commands → run
- `config/app.php` — config schema (env-var name, type, default)
- `src/Service/ConfigService.php` — custom `.env` parser + typed config resolver
- `src/Service/LocalizationService.php` — translation loader/cache
- `src/Service/ConsoleCommandService.php` — shared CLI input helpers
- `src/Helpers/helpers.php` — global `__()` function
- `lang/en/console.php` — all English UI strings (reference for adding translations)
- `samples/` — real audio files usable for manual testing
- `src/Command/RunAllCommand.php` — orchestration pipeline for `music:all` (delegates to existing commands)
- `src/Service/Mp3ResortService.php` — resort flow + parallel worker helpers (`listFiles()`, `processFilesFromList()`)
- `src/Service/FileResortService.php` — artist folder/file name sanitization and move/rename collision handling
- `music` — root wrapper script that proxies all args to `bin/console` (Linux/macOS/WSL)
- `music.bat` — root wrapper script that proxies all args to `bin/console` (Windows cmd)
