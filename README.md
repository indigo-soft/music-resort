# music-resort

CLI tool for sorting, deduplicating, fixing extensions, and cleaning local music libraries
(mp3, flac, m4a) — fully offline, no external services required.

Read in Ukrainian: [docs/uk/README.md](docs/uk/README.md)

---

## Requirements

| Dependency | Version |
|-----------|---------|
| PHP | ≥ 8.5 |
| Composer | any recent |
| Node.js | ≥ 24 (dev only) |
| pnpm | ≥ 10 (dev only) |

---

## Installation

### Production (use the tool on real files)

```bash
git clone https://github.com/indigo-soft/music-resort
cd music-resort
composer install --no-dev --optimize-autoloader
cp .env.example .env
```

### Development

```bash
git clone https://github.com/indigo-soft/music-resort
cd music-resort
composer install
pnpm install        # commitlint, lefthook, release-it
pnpm prepare        # install git hooks
cp .env.example .env
```

Edit `.env` for local development:

```dotenv
DEBUG=true          # forces --dry-run globally — no files are modified
DEFAULT_LANG=en     # ui locale: en or uk
```

---

## Usage

### Run the full pipeline

```bash
php bin/console music:all <source> [destination] [--dry-run] [--concurrency=N]
```

Pipeline order:
1. `music:resort` — sort by artist (skipped if no destination)
2. `music:fix-extensions` — fix extensions from metadata
3. `music:deduplicate` — remove duplicates
4. `music:clean` — remove corrupted files
5. `music:clean-empty-dirs` — remove empty directories

### Individual commands

```bash
php bin/console music:resort <source> <destination> [--dry-run] [--concurrency=N]
php bin/console music:fix-extensions <source> [--dry-run]
php bin/console music:deduplicate <source> [--dry-run]
php bin/console music:clean <source> [--dry-run]
php bin/console music:clean-empty-dirs <source> [--dry-run]
```

### Short wrappers

```bash
./music music:all <source> [destination]     # Linux / macOS / WSL
music.bat music:all <source> [destination]   # Windows cmd
```

### Composer aliases

```bash
composer music:list
composer music:all -- <source> [destination] [--dry-run]
composer music:resort -- <source> <destination> [--dry-run]
```

> Use `--` after the composer script name to pass arguments to `bin/console`.

### `--dry-run` mode

No files are moved, renamed, or deleted. Console output is identical to a real run.
Always test with `--dry-run` before processing a real library.

Setting `DEBUG=true` in `.env` enables dry-run globally for all commands.

---

## Key features

- **Sort by artist** — reads `artist`, `albumartist`, `band`, `performer` tags (in that order)
- **Multi-artist splitting** — picks the first artist from `;`, `,`, `/`, `&`, `feat.`, `ft.`, `featuring`
- **Folder name sanitization** — removes invalid characters, limits to 100 characters
- **Parallel resort** — `--concurrency=N` spawns N worker processes (cross-platform)
- **Collision handling** — auto-renames on filename conflict
- **Localization** — EN and UK interface

---

## Testing

```bash
composer test               # all tests
composer test:unit          # unit tests only
composer test:integration   # integration tests (--dry-run on samples/)
```

See [docs/guides/testing.md](docs/guides/testing.md) for the full testing guide.

---

## Documentation

| Document | Description |
|----------|-------------|
| [docs/guides/git-workflow.md](docs/guides/git-workflow.md) | Branch naming, commit format, PR flow |
| [docs/guides/coding-standards.md](docs/guides/coding-standards.md) | PHP style, linting, forbidden patterns |
| [docs/guides/deployment.md](docs/guides/deployment.md) | Setup steps for dev and production |
| [docs/guides/testing.md](docs/guides/testing.md) | Testing strategy, conventions, commands |
| [docs/architecture/overview.md](docs/architecture/overview.md) | System overview and diagram |
| [docs/architecture/components.md](docs/architecture/components.md) | Component responsibilities |
| [docs/architecture/modules.md](docs/architecture/modules.md) | Module boundaries and rules |
| [docs/adr/INDEX.md](docs/adr/INDEX.md) | Architecture Decision Records |
| [AGENTS.md](AGENTS.md) | Guide for AI agents working in this repo |
| [CHANGELOG.md](CHANGELOG.md) | Release history |
| [ROADMAP.md](ROADMAP.md) | Planned milestones |
| [SECURITY.md](SECURITY.md) | Security policy |

---

## License

[MIT](LICENSE.md)
