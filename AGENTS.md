# AGENTS Guide

## What this repository is

**music-resort** is a PHP 8.5+ CLI tool built on Symfony Console for sorting, deduplicating,
fixing extensions, and cleaning music files (mp3, flac, m4a). It is a standalone command-line
application with no web interface, no database, and no external service dependencies.

Entry point: `bin/console`. Namespace: `MusicResort\` → `src/`.

## Repository map

```
bin/
    console                     # bootstrap: init config → set locale → register commands → run
config/
    app.php                     # config schema (env-var name, type, default)
docs/
    adr/                        # Architecture Decision Records
    checklists/                 # project setup checklists (Claude updates these)
    context/                    # project memory (project.md, decisions.md)
    uk/                         # Ukrainian documentation
lang/
    en/console.php              # English UI strings (reference for adding translations)
    uk/console.php              # Ukrainian UI strings
samples/                        # real audio files for manual testing
src/
    Command/                    # Symfony Console commands (thin layer, delegates to Service)
    Service/                    # business logic (all dependencies via constructor injection)
    Exception/                  # custom exception classes
    Helpers/
        helpers.php             # global __() translation function
        ResortMp3Helper.php     # parallel worker coordinator and spawner
music                           # short wrapper for bin/console (Linux/macOS/WSL)
music.bat                       # short wrapper for bin/console (Windows cmd)
composer.json                   # PHP dependencies and composer scripts
```

## Available commands

### PHP / Composer

| Command | Description |
|---------|-------------|
| `composer install` | Install all PHP dependencies |
| `composer test` | Run Pest tests (`pest -d`) |
| `composer cs` | Check code style without changes (PHP CS Fixer dry-run) |
| `composer fix` | Auto-fix code style (PHP CS Fixer) |
| `composer stan` | Static analysis (PHPStan, config: `phpstan.neon.dist`) |
| `composer lint` | Fix + stan combined |
| `composer music:list` | List all available console commands |
| `composer music:all -- <src> [dest]` | Run full pipeline (resort → fix → deduplicate → clean) |
| `composer music:resort -- <src> <dest>` | Sort music files into artist folders |

### Console (direct)

| Command | Description |
|---------|-------------|
| `php bin/console music:all <src> [dest] [--dry-run] [--concurrency=N]` | Full pipeline |
| `php bin/console music:resort <src> <dest> [--dry-run] [--concurrency=N]` | Sort by artist |
| `php bin/console music:deduplicate <src> [--dry-run]` | Remove duplicate audio files |
| `php bin/console music:fix-extensions <src> [--dry-run]` | Fix extensions from metadata |
| `php bin/console music:clean <src> [--dry-run]` | Remove corrupted/invalid files |
| `php bin/console music:clean-empty-dirs <src> [--dry-run]` | Remove empty directories |
| `php bin/console list` | List all available commands |

### Short wrappers

```bash
./music list                          # Linux/macOS/WSL
music.bat list                        # Windows cmd
```

### Node.js / pnpm (dev tooling)

| Command | Description |
|---------|-------------|
| `pnpm install` | Install Node.js dev dependencies (commitlint, lefthook, release-it) |
| `pnpm prepare` | Install lefthook git hooks + make scripts executable |
| `pnpm release:dry` | Dry-run release — shows what would happen |
| `pnpm release:patch` | Release a patch version bump |
| `pnpm release:minor` | Release a minor version bump |
| `pnpm release:major` | Release a major version bump |

> ⚠️ `package.json` is not yet present — run the Node.js tooling setup first.

## Conventions agents must follow

### General
- All PHP files must begin with `declare(strict_types=1)`.
- Use `final` for all service classes.
- Never add business logic to `Command` classes — delegate everything to `Service`.
- Never check `--dry-run` option directly in service classes — always use `ConsoleCommandService::isDryRun()`,
  which combines the `--dry-run` flag AND the `app.debug` config value.
- Translation keys for "would do X" (dry-run) live under `note.*`, actual actions under `info.*`.
- When adding UI strings: always add to both `lang/en/console.php` and `lang/uk/console.php`.
- `getid3` is the only metadata library — do not introduce alternatives.

### Dependency Injection — mandatory check

**⚠️ This project uses constructor injection. Violating this is a critical error.**

When writing any new service:

```php
// ✅ Correct
final class MyService
{
    public function __construct(
        private readonly SomeDependency $dep,
    ) {}
}
```

When reviewing any code — check:
1. No calls to `ConfigService::get()` or `LocalizationService::*` inside new service classes.
2. No dependencies created via `new` inside service methods.
3. All dependencies declared in the constructor.

**Allowed exceptions** (existing legacy, do not migrate):
- `ConfigService` and `LocalizationService` themselves
- Global helper `__()` — allowed everywhere

All `new SomeService(...)` calls — only in `bin/console`. Never inside other services.

**When a violation is found — always point it out, never stay silent.**

### Naming
- Follow `docs/guides/naming-conventions.md`.
- Folder name sanitization max length: 100 characters (`FileResortService::sanitizeFolderName()`).

### Commits and branches
- Follow Conventional Commits: `<type>(<scope>): <description>`.
- Valid scopes: `command`, `service`, `exception`, `helpers`, `lang`, `config`, `docs`, `deps`.
- Branch format: `<type>/<issue-number>-<description>` (issue number min 4 digits).
- Do not modify `composer.lock` or `pnpm-lock.yaml` manually.

## Tooling and workflows

### PHP tooling
| Tool | Config | Purpose |
|------|--------|---------|
| PHP CS Fixer | `.php-cs-fixer.dist.php` | Code style formatting |
| PHPStan | `phpstan.neon.dist` | Static analysis |
| Pest | — | Testing framework |

### Node.js dev tooling
| Tool | Config | Purpose |
|------|--------|---------|
| commitlint | `commitlint.config.js` | Commit message validation |
| Lefthook | `lefthook.yml` | Git hooks (pre-commit, commit-msg, pre-push) |
| release-it | `scripts/.release-it.json` | Automated changelog and GitHub releases |

### Git hooks (Lefthook)
- `pre-commit` — runs PHP CS Fixer on staged files
- `commit-msg` — validates commit message via commitlint
- `pre-push` — runs PHPStan + Pest

### Environment variables (`.env` in project root)

| Variable | Type | Default | Purpose |
|----------|------|---------|---------|
| `DEBUG` | bool | `false` | Forces dry-run globally across all commands |
| `DEFAULT_LANG` | string | `en` | UI locale (`en` or `uk`) |

> Local development: set `DEBUG=true` to enable global dry-run (no files are moved/deleted).

## Architecture and integration notes

### Command → Service pattern
Each command in `src/Command/` is a thin layer: reads CLI input via `ConsoleCommandService`,
delegates all logic to a matching service in `src/Service/`.

### Key services
- `ConsoleCommandService` — centralises `source`, `destination`, `dry-run` extraction from CLI input.
- `ConfigService` — static, called once in `bin/console`. Reads `.env` (custom parser) then `config/app.php`.
  Access via `ConfigService::get('app.debug')` (dot-notation).
- `LocalizationService` — translation loader/cache.
- `MusicMetadataService` — single integration point for `getid3`. Tags checked in order:
  `artist`, `albumartist`, `band`, `performer`. Multi-artist separators: `; , / & feat. ft. featuring`.
- `FileResortService` — folder name sanitization and file move/rename collision handling.
- `Mp3ResortService` — resort flow. Includes `*.mp3`, `*.flac`, `*.m4a`; excludes `@eaDir`,
  `.AppleDouble`, `.AppleDB`.

### Parallel workers (music:resort only)
`--concurrency=N` splits files into batches and spawns child PHP processes via `symfony/process`.
Internal options `--worker-batch` and `--result-json` are worker-coordination only — do not use directly.
See [ADR-0005](docs/adr/ADR-0005-parallel-processing-child-processes.md).

### No external services
This project has no database, no HTTP API, no queue, and no cloud service dependencies.
All operations are local filesystem reads and writes.

## Agent operating guidance

**Adding a new command — required steps:**
1. Create `src/Command/MyCommand.php` extending `Symfony\Component\Console\Command\Command` with `#[AsCommand]`.
2. Create `src/Service/MyService.php` with business logic. Inject all dependencies via constructor.
3. Register in `bin/console`: instantiate dependencies first, then `$application->add(new MyCommand($myService))`.
4. Add translation keys to `lang/en/console.php` and `lang/uk/console.php`.
5. Use `ConsoleCommandService` to read `source`, `destination`, `dry-run`.

**DO:**
- Read `docs/context/project.md` at the start of any session.
- Use `ConsoleCommandService::isDryRun()` — never check `--dry-run` option directly in services.
- Check both language files (`lang/en/` and `lang/uk/`) when adding or changing UI strings.
- Append to `docs/context/decisions.md` after any non-obvious architectural choice.
- Update `docs/checklists/new-project.md` after completing a setup task.
- Point out constructor injection violations — never stay silent.

**DO NOT:**
- Add business logic to `Command` classes.
- Create `new SomeService(...)` inside other services — only in `bin/console`.
- Call `ConfigService::get()` or `LocalizationService::*` inside new service classes.
- Modify `composer.lock` or `pnpm-lock.yaml` manually.
- Introduce a second metadata library alongside `getid3`.
- Use `--worker-batch` or `--result-json` options directly — they are internal worker flags.
- Create files outside `src/`, `lang/`, `config/`, `docs/`, `bin/`.
