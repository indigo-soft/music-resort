# AID-0001: Documentation Foundation Sprint

## Status

Accepted

## AI System

Claude Sonnet 4.6 (claude.ai, Music.local project)

## Date

2026-05-11 — 2026-05-15

## Author

Kira (solo developer)

---

## Goal

Establish a complete documentation foundation for the music-resort project using the
docs.template structure. The project had working code but no formal documentation:
no ADRs, no architecture docs, no coding standards, no onboarding context.

Goals for the sprint:

1. Capture five existing architectural decisions as ADRs (FFmpeg, DI, getID3, Symfony Console,
   parallel processing).
2. Rewrite `AGENTS.md` in English with the docs.template structure and a DI enforcement section.
3. Generate architecture documentation (`overview.md`, `components.md`, `modules.md`).
4. Generate guides (`coding-standards.md`, `deployment.md`, `testing.md`).
5. Generate project meta files (`README.md`, `CHANGELOG.md`, `ROADMAP.md`, `SECURITY.md`).
6. Set up Node.js dev tooling (`package.json`, `commitlint.config.mjs`, `lefthook.yml`).
7. Collect core project facts into `docs/context/project.md`.

---

## Prompt Summary

The sprint used structured prompts from `docs/prompts/` in the docs.template project.
Each prompt was run in sequence:

1. **`docs/prompts/onboarding.md`** — collect project facts and generate `docs/context/project.md`.
2. **`docs/prompts/agents.md`** — rewrite `AGENTS.md` using docs.template structure.
3. **`docs/prompts/adr.md`** — applied five times to write each ADR.
4. **`docs/prompts/architecture.md`** — generate three architecture documents.
5. **`docs/prompts/coding-standards.md`** — inspect config files and generate the guide.
6. **`docs/prompts/deployment.md`** — document local and production setup.
7. **`docs/prompts/testing.md`** — document the Pest-based test infrastructure.
8. **`docs/prompts/readme.md`** — rewrite `README.md`.

ADR content was drawn from two Ukrainian-language PDFs (ADR-0001, ADR-0002) provided by the
developer, and from the existing `AGENTS.md` draft patch. The remaining three ADRs (0003–0005)
were written by Claude from scratch based on AGENTS.md, source code inspection, and
the Filesystem MCP connector.

---

## AI Output Summary

All generated from scratch or substantially rewritten. Files created or overwritten:

**Phase 1 — Foundation**

- `docs/context/project.md` — core project facts
- `docs/context/decisions.md` — initialized with onboarding entry
- `AGENTS.md` — full rewrite in English with DI enforcement section, ADR table, Node.js commands

**Phase 2 — ADRs** (`docs/adr/`)

- `ADR-0001-ffmpeg-audio-processing.md` — translated and expanded from Ukrainian PDF
- `ADR-0002-dependency-injection.md` — translated and expanded from Ukrainian PDF
- `ADR-0003-getid3-metadata-library.md` — written from source code and AGENTS.md
- `ADR-0004-symfony-console-framework.md` — written from source code and composer.json
- `ADR-0005-parallel-processing-child-processes.md` — written from source code, includes ASCII worker protocol diagram

**Phase 3 — Architecture** (`docs/architecture/`)

- `overview.md` — system purpose, boundaries, key constraints
- `components.md` — per-component responsibilities
- `modules.md` — module boundaries with "Hard Module Boundaries" summary table

**Phase 4 — Guides** (`docs/guides/`)

- `coding-standards.md` — extracted from `.php-cs-fixer.dist.php`, `phpstan.neon.dist`,
  `commitlint.config.mjs`, source code inspection
- `deployment.md` — local dev and production setup
- `testing.md` — Pest unit + integration structure, with stub test files generated

**Phase 5 — Project meta**

- `README.md` — rewritten with commands table, requirements, docs index
- `CHANGELOG.md` — initialized for release-it
- `ROADMAP.md` — three milestones: v1.0.0 (MVP), v1.1.0 (DI + tests), v1.2.0 (CI/CD)
- `SECURITY.md` — minimal policy for a local CLI tool

**Phase 6 — Node.js tooling**

- `package.json` — created (release scripts, engines, devDependencies)
- `commitlint.config.mjs` — all rules inline, no extends, project scopes
- `lefthook.yml` — PHP-specific hooks with visible `fail_text`
- `.pnpmrc` — `approve-builds=lefthook`

---

## Evaluation

Reviewed manually by the developer after each phase. Specific checks:

- ADR content verified against the original Ukrainian PDFs and working codebase behaviour.
- `AGENTS.md` DI section cross-checked against the existing patch document.
- Architecture docs validated against actual `src/` structure via Filesystem MCP.
- `coding-standards.md` validated against actual config files (no paraphrasing — rules
  extracted directly from `.php-cs-fixer.dist.php` and `phpstan.neon.dist`).
- Node.js tooling validated by running `pnpm run init`, committing with invalid message
  (rejected), running `npm run release:dry` (passed all pre-release checks).
- 14/14 Pest tests pass after test infrastructure was generated.

One correction required during the sprint: `coding-standards.md` initially described a DI
violation in `Mp3ResortService::processSingleFile()` as an "allowed pattern". This was
corrected to "tech debt to fix" after developer review.

---

## Outcome

**Adopted as-is:**

- All five ADRs — content and structure used without modification after review.
- `docs/context/project.md` — used as-is.
- Architecture documents — used as-is.
- Node.js tooling files — used as-is after validation.

**Adopted with corrections:**

- `AGENTS.md` — Node.js command section corrected in a later sync session:
  `pnpm prepare` → `pnpm run init`, `pnpm release:*` → `npm run release:*`.
- `coding-standards.md` — DI violation reclassified from "allowed pattern" to "tech debt".

**Not applicable / rejected:**

- No output was fully rejected. All generated content was either used directly or after
  minor correction.

---

## Project Impact

The sprint took the project from zero formal documentation to a complete docs foundation.

Files created or rewritten: 20+
- `docs/context/project.md`, `docs/context/decisions.md`
- `AGENTS.md`
- `docs/adr/` — 5 ADRs
- `docs/architecture/` — 3 files
- `docs/guides/` — 3 files
- `README.md`, `CHANGELOG.md`, `ROADMAP.md`, `SECURITY.md`
- `package.json`, `commitlint.config.mjs`, `lefthook.yml`, `.pnpmrc`

Related ADRs — all five:

- [ADR-0001](../adr/ADR-0001-ffmpeg-audio-processing.md)
- [ADR-0002](../adr/ADR-0002-dependency-injection.md)
- [ADR-0003](../adr/ADR-0003-getid3-metadata-library.md)
- [ADR-0004](../adr/ADR-0004-symfony-console-framework.md)
- [ADR-0005](../adr/ADR-0005-parallel-processing-child-processes.md)

---

## Lessons Learned

**What worked well:**

- **Sequential prompt execution** — running prompts in order (onboarding → AGENTS →
  ADRs → architecture → guides → meta) produced coherent output with minimal re-work.
  Each step could silently use the previous step's output.
- **Source code inspection before asking questions** — for architecture and coding standards,
  Claude read actual files first and asked zero or near-zero questions. Output was concrete,
  not generic.
- **Filesystem MCP connector** — direct disk access eliminated the copy-paste loop. Files
  were written atomically without manual intervention.
- **Existing Ukrainian PDFs as ADR seeds** — providing the original decision rationale in
  PDF form (even in another language) gave Claude enough context to write accurate, detailed
  ADRs without guessing.
- **Separate DI patch document** — having the DI rules in a dedicated document made it easy
  to merge them precisely into `AGENTS.md`.

**What to do differently next time:**

- **Validate Node.js commands immediately** — the `pnpm prepare` vs `pnpm run init` error
  and `pnpm release:*` vs `npm run release:*` error persisted until a later sync session.
  A quick `pnpm run init --help` check during the sprint would have caught both.
- **Flag tech debt violations explicitly** — during coding standards generation, a DI
  violation was softened to "allowed pattern" instead of "tech debt". A stricter framing
  in the prompt ("if you find a violation, always label it tech debt, never an exception")
  would prevent this.
- **Write the AID during the sprint** — this AID was written two weeks after the sprint
  ended, relying on `decisions.md` and conversation history. Key details (exact prompt
  wording, developer feedback per step) were partially lost. Writing AIDs immediately
  after the session preserves more nuance.

**AI strengths for this type of task:**

- Translating Ukrainian decision rationale to formal English ADR structure — accurate,
  no hallucination when source material was provided.
- Extracting rules from config files — concrete and correct, not vague paraphrasing.
- Writing ASCII diagrams (ADR-0005 worker protocol) — first attempt was accurate.

**AI limitations observed:**

- Command correctness: Claude reproduced `pnpm prepare` from an earlier draft without
  cross-checking the actual `package.json` scripts section. Always verify generated
  command tables against the actual config files.
