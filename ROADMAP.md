# Roadmap

This roadmap tracks planned milestones. Each milestone corresponds to a release.
Detailed decisions are documented in [docs/adr/INDEX.md](docs/adr/INDEX.md).

---

## v1.0.0 — MVP Release ✅ (in progress)

Initialize the existing codebase as the first official release.

**Scope:**
- [x] Core commands: `music:resort`, `music:deduplicate`, `music:fix-extensions`,
      `music:clean`, `music:clean-empty-dirs`, `music:all`
- [x] `--dry-run` support on all commands
- [x] `--concurrency=N` parallel workers for `music:resort`
- [x] EN + UK localization
- [x] Project documentation: AGENTS.md, architecture, guides, ADRs
- [x] Node.js dev tooling: commitlint, lefthook, release-it
- [ ] Initial test suite (unit + integration stubs passing)
- [ ] `pnpm release:minor` — tag v1.0.0, publish GitHub Release

---

## v1.1.0 — Test Coverage & DI Fix

**Scope:**
- [ ] Fix DI violation in `Mp3ResortService::processSingleFile()`
      (extract `MusicMetadataService` and `FileResortService` instantiation to constructor)
- [ ] Extract `extractFirstArtist` to a testable static helper
- [ ] Extract `sanitizeFolderName` to a testable static helper
- [ ] Write real unit tests for extracted helpers
- [ ] Write integration tests for all commands against `samples/`
- [ ] PHPStan level bump (1 → 2)

---

## v1.2.0 — CI/CD Pipeline

**Scope:**
- [ ] GitHub Actions: lint → test on every push and pull request
- [ ] GitHub Actions: release automation on tag push (`v*.*.*`)
- [ ] `composer audit` in CI pipeline
- [ ] Coverage reporting

---

## Backlog (unscheduled)

- [ ] `music:rename` — rename files using metadata pattern (e.g. `Artist - Title.mp3`)
- [ ] `music:report` — dry-run summary exported to JSON or CSV
- [ ] PHPStan level 5+
- [ ] Watch mode — monitor a folder and auto-sort new files
