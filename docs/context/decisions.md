# Decisions Log

> Append-only log of decisions made during project setup and development.
> Format: newest entries at the top.

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
