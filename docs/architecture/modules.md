# Architecture Modules — music-resort

## Module Structure

The codebase is organised into 5 PHP namespaces under `src/` plus 3 supporting directories.
There are no package boundaries enforced by a build tool — module boundaries are enforced
by the constructor injection rule: **services do not instantiate other services**.
All wiring happens in `bin/console`.

---

## PHP Modules (`src/`)

### `MusicResort\Command`

**Purpose:** CLI entry points. Each class maps one-to-one to a Symfony Console command.
Commands are the only public-facing interface of the application.

**Public API (what other modules may use):** nothing — commands are leaf nodes.

**Allowed to use:**
- `MusicResort\Service\ConsoleCommandService` (shared input helper)
- Symfony Console API (`InputInterface`, `OutputInterface`, `SymfonyStyle`)

**Forbidden dependencies:**
- Must NOT call `MusicMetadataService`, `FileResortService`, or any domain service directly
- Must NOT contain business logic — only input reading and delegation
- Must NOT instantiate services with `new` — all services arrive via constructor injection

---

### `MusicResort\Service`

**Purpose:** All business logic. Each service owns one concern (metadata, resort, deduplication,
extension fixing, cleaning, config, localization, console input).

**Public API (what other modules may use):**
- `Command` layer uses `ConsoleCommandService`
- `bin/console` wires all services via constructor injection
- `MusicMetadataService` is used by resort, deduplicate, and fix-extensions services
- `FileResortService` is used by `Mp3ResortService`

**Allowed to use:**
- Other services — only via constructor injection (no `new` inside service methods)
- `getid3` — only from `MusicMetadataService` (no other service may use it directly)
- Global helper `__()` — allowed anywhere
- `symfony/finder`, `symfony/filesystem`, `symfony/process`, `symfony/string`

**Forbidden dependencies:**
- Must NOT call `ConfigService::get()` or `LocalizationService::*` directly
  (exception: `ConfigService` and `LocalizationService` themselves)
- Must NOT instantiate other services with `new`
- Must NOT contain CLI-specific code (`InputInterface`, `SymfonyStyle`)

---

### `MusicResort\Exception`

**Purpose:** Custom exception classes for domain-specific error conditions.

**Public API:** all exception classes (thrown by services, handled in commands or bin/console)

**Allowed to use:** nothing (pure value objects / markers)

**Forbidden dependencies:** none (must remain dependency-free)

---

### `MusicResort\Helpers` (namespace)

**Purpose:** Global utilities and parallel worker coordination.

Contains:
- `helpers.php` — autoloaded file defining the global `__()` function (localization shortcut)
- `ResortMp3Helper.php` — parallel worker coordinator (spawns child PHP processes)

**Public API:**
- `__()` function — available globally everywhere
- `ResortMp3Helper` — used only by `Mp3ResortService`

**Forbidden dependencies:**
- `ResortMp3Helper` must not be used outside of `Mp3ResortService`
- `helpers.php` must not import service classes

---

## Supporting Directories

### `lang/`

**Purpose:** Localisation files.

```
lang/
    en/console.php    ← reference; all keys defined here
    uk/console.php    ← must mirror all keys from en/
```

**Rules:**
- When adding a new key: add to **both** `en/console.php` and `uk/console.php`
- Key naming: dot-notation nested arrays — e.g. `note.resort.dry_run`, `info.resort.moved`
- `note.*` — dry-run messages ("would do X")
- `info.*` — actual action messages ("did X")
- `error.*` — error messages

---

### `config/`

**Purpose:** Static application configuration schema.

```
config/
    app.php    ← config schema (env-var name, type, default value per key)
```

All config values are read from `.env` and validated against this schema by `ConfigService`.
Do not add hardcoded values to application code — add them to `config/app.php` as a new key.

---

### `bin/`

**Purpose:** Application entry point and dependency wiring.

```
bin/
    console    ← bootstrap script (not a module; not namespaced)
```

`bin/console` is the only place where:
- `ConfigService::init()` is called
- `LocalizationService` is initialised
- Services are instantiated with `new`
- Commands are registered with `$application->add(...)`

**Rule:** All dependency wiring happens here and only here.

---

## Dependency Flow

```
bin/console  (wiring only)
    │
    ├── instantiates all services with `new`
    └── passes them into commands via constructor
            │
            └── Command layer  (delegates to services)
                    │
                    └── Service layer  (business logic)
                            │
                            ├── MusicMetadataService  (getid3 — isolated here)
                            ├── FileResortService
                            ├── ConsoleCommandService
                            ├── ConfigService  ─── config/app.php + .env
                            └── LocalizationService ─── lang/<locale>/console.php
```

## Hard Module Boundaries

| Boundary | Rule |
|----------|------|
| `getid3` | Only `MusicMetadataService` may use it |
| `new SomeService()` | Only in `bin/console` |
| `ConfigService::get()` | Only in `ConfigService` itself and `bin/console` |
| `LocalizationService::*` | Only in `LocalizationService` itself and `bin/console` |
| Business logic | Only in `Service` — never in `Command` |
| CLI code (`InputInterface`) | Only in `Command` and `ConsoleCommandService` — never in `Service` |
| `--worker-batch` / `--result-json` | Internal to `ResortMp3Helper` — never used directly |
