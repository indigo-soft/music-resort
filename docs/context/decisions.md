# Decisions Log

> Append-only log of decisions made during project setup and development.
> Format: newest entries at the top.

---

## 2026-06-13 — Tech debt: resort-pipeline DI audit deferred (v1.1.0)

While building `metadata:scan` we confirmed the resort pipeline still has
un-injected `new` calls for our domain services, and that the entry point
(`ResortMp3Command`) drives a `ResortMp3Helper` + child-worker spawn (ADR-0005)
rather than `Mp3ResortService` directly. A proper DI cleanup here requires
reading `ResortMp3Helper` and untangling the parallel worker bootstrap, so it
was deliberately deferred to its own session rather than bundled with the scan
work (risk of regressing a working parallel pipeline).

Scope of the deferred audit:
- `Mp3ResortService`, `FileResortService`: domain services that should be wired
  in `bin/console` instead of `new`-ed inline.
- `MusicMetadataServiceFactory`: the `new MusicMetadataService($filePath)` inside
  is an intentional factory pattern (per-file object) and is NOT a violation —
  keep as-is.
- Per-invocation console objects (`SymfonyStyle`, `ConsoleCommandService`) depend
  on `$input`/`$output` and cannot be assembled at bootstrap; creating them in
  `execute()` is correct and out of ADR-0002 scope.
- `new Filesystem()` is a stateless Symfony component, not a hidden dependency;
  injecting it is optional cleanup, not a violation.

This maps to the existing v1.1.0 "DI fix" roadmap milestone.

---

## 2026-06-13 — metadata:scan command (collection inventory)

The `music_file_metadata` table (migration 003) and `MusicFileMetadataRepository`
existed since the 002/003 work, but no command ever populated the table — the MVP
resort pipeline reads getID3 metadata and moves files without persisting anything.
This left `metadata:enrich` with an empty artist source (`findAllArtists()` returned
nothing). Added `metadata:scan` to close the gap: it walks the collection with
Finder (same `mp3|flac|m4a` mask as resort), reads metadata via
`MusicMetadataService` (getID3, ADR-0003), and upserts one row per file.

**MusicMetadataService extended** with getters for the columns it previously did not
expose: `getAlbum`, `getAlbumArtist`, `getTrackNumber` (kept as string to preserve
`03/12` notation), `getYear`, `getComment`, `getTagSource`. `extractTags()` no longer
throws when a file has no recognised tag layer — it returns `[]` and `tag_source`
becomes null, so untagged files are still inventoried (status active, artist null)
instead of aborting the scan. This is an additive change to an existing MVP class;
resort behaviour is unaffected (a null artist still routes to the Unknown_Artist
fallback as before).

**Error policy:** unreadable files (getID3 parse failure) are marked via
`markUnreadable()` + warning and the scan continues; a single bad file never aborts
the run. No `--dry-run` — the command touches no files, only reads them and writes to
the DB (consistent with `metadata:enrich`).

---

## 2026-06-12 — Last.fm enrichment pipeline (migration 004, metadata:enrich)

**HTTP layer:** New `MusicResort\Http` namespace. `HttpClientInterface` (single `get()`
method) + `PhpHttpClient` (`final readonly`) built on `file_get_contents` with a stream
context (10 s timeout, custom User-Agent, `ignore_errors` to read error bodies). Status
code read via `http_get_last_response_headers()` (PHP 8.4+, replaces the scope-fragile
`$http_response_header` magic variable), last status line wins to handle redirects. Zero
new composer dependencies — deliberate, per minimal-dependency philosophy and the ADR-0004
component whitelist.

**lastfm_artist_tags (migration 004):** One row per artist, `tags` is a JSON column
storing `[{name, count}]` — counts preserved because mood resolution will use them as
priority weights. Upsert refreshes `tags` + `fetched_at`. TTL evaluated at query time
(`fetched_at >= NOW() - INTERVAL :ttl DAY`), no eviction job. DDL split into separate
statements, no transaction wrapping (MariaDB implicit commit).

**Error policy:** one retry per artist on transport/API failure, then skip + warning to
processing_log; a failed artist never aborts the run. Command exits FAILURE only when all
artists failed. No `--dry-run` (touches no files); `--limit=N` covers the small-batch case.

**Rate limiting:** fixed 250 ms pause before each live API call (cache hits skip the pause).

**Config:** `LASTFM_API_KEY`, `LASTFM_API_URL`, `LASTFM_CACHE_TTL_DAYS` (int, default 30).
Key absence validated in the command, not bootstrap, so the rest of the app works without a key.

---

## 2026-05-28 — SQLite migrations 002 & 003; DatabaseLoggerService

**processing_log (migration 002):** Structured log table replaces FileLoggerService as
the single log sink for all commands. Key design choice: `run_id` (UUIDv4, generated
once per process in bin/console) groups all entries from one invocation for easy
per-run querying. `context` is nullable JSON — omitted when empty to save space.

**music_file_metadata (migration 003):** Collection inventory table. Upsert semantics
via `INSERT ... ON CONFLICT(file_path) DO UPDATE` — repeated scans refresh, not
duplicate. `album_artist` stored separately from `artist` to handle compilations.
`track_number` is TEXT to preserve `03/12` notation. `tag_source` records which
getID3 tag layer won (ADR-0003 priority: id3v2 → id3v1 → quicktime → vorbiscomment).

**DatabaseLoggerService:** Implements LoggerInterface, writes to processing_log via
ProcessingLogRepository. FileLoggerService is no longer wired in bin/console.
Logger not yet injected into individual commands — their constructors are still
closed (existing tech debt, tracked for v1.1.0 DI fix). The $logger instance in
bin/console currently covers only the top-level catch block.

---

## 2026-05-28 — AID-0001: Documentation Foundation Sprint

**Summary:** Claude wrote all 5 ADRs, rewrote AGENTS.md, and generated the full docs
foundation (architecture, guides, meta files, Node.js tooling) in a single sprint;
all output adopted with minor corrections.
**Full record:** docs/aid/AID-0001-documentation-foundation-sprint.md

---

## 2026-05-28 — Synced docs structure with docs.template

**Changes applied:**
- `AGENTS.md`: fixed Node.js commands (`pnpm prepare` → `pnpm run init`,
  `pnpm release:*` → `npm run release:*`); added "Global tools required" section;
  added Glossary and AID conventions to "Conventions agents must follow" and DO/DO NOT.
- Created `docs/glossary/glossary.md` — project-specific glossary seeded from docs.template.
- Created `docs/air/` (README.md + INDEX.md) — Architecture Issue Records directory.
- Created `docs/aid/` (README.md + INDEX.md + archive/) — AI Interaction Documents directory.
- Created `docs/checklists/code-review.md`, `release.md`, `new-feature.md` — copied from docs.template.

---

## 2026-05-15 — Phase 4 project meta files created

**README.md:** Rewritten from scratch using docs.template structure.
- Kept all existing command documentation and examples
- Added: requirements table, dev vs prod install split, docs index table, testing commands
- Removed: "Testing (planned)" section — replaced with real testing commands
- Added links to all docs, AGENTS.md, CHANGELOG, ROADMAP, SECURITY

**CHANGELOG.md:** Initialized with release-it compatible format.
`<!-- CHANGELOG -->` marker is the insertion point for release-it.
Do not edit manually.

**SECURITY.md:** Minimal policy appropriate for a local CLI tool with no network,
no API, no auth. Reporting via GitHub Security Advisories.
`composer audit` added as the dependency security check.

**ROADMAP.md:** Three milestones defined:
- v1.0.0 — MVP release (current codebase, tests passing, first tag)
- v1.1.0 — DI fix + real test coverage + PHPStan bump
- v1.2.0 — CI/CD pipeline

Rationale for v1.0.0 as first release: the core functionality (all 5 commands) is working
and has been in use. The MVP release formalizes this as the starting point of the
versioned release cycle.

---

## 2026-05-15 — Testing infrastructure set up

Unit + Integration split. No E2E (no HTTP API, no DB).
Stubs created: artist extraction unit test, smoke integration tests for all commands.
Tech debt surfaced: private `extractFirstArtist`, private `sanitizeFolderName`, DI violation in
`Mp3ResortService` — all block proper unit testing, tracked in v1.1.0 roadmap.

---

## 2026-05-15 — Deployment guide generated

Dev: `composer install` + `pnpm install` + `DEBUG=true`.
Prod: `composer install --no-dev` + `DEBUG=false`. No server, no Docker, no remote deployment.

---

## 2026-05-15 — coding-standards.md corrected

DI violation in `Mp3ResortService::processSingleFile()` is tech debt to fix, not an exception.

---

## 2026-05-15 — Coding standards documented

From config file inspection only. PHPStan level 1 (early stage).
Translation key convention (`info.*` / `note.*` / `error.*` / `warning.*`) made explicit.

---

## 2026-05-15 — Architecture documentation generated

Zero questions — all from AGENTS.md + README.md + source code.
Legacy static singletons documented. Module boundaries made explicit.

---

## 2026-05-15 — AGENTS.md rewritten to match docs.template structure

Preserved all project-specific conventions. Added Node.js tooling commands.

---

## 2026-05-15 — Project onboarding completed

**Stack:** PHP 8.5+ + Symfony Console 7.3
**Deployment:** local CLI tool
**Environments:** local (DEBUG=true), production (DEBUG=false)
**Commit scopes:** command, service, exception, helpers, lang, config, docs, deps
