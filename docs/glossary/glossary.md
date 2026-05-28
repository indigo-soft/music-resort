# Glossary

Terms used across this project's documentation and tooling.
Entries are sorted alphabetically. Every README should link the first occurrence
of each term using `[term](docs/glossary/glossary.md#term-anchor)`.

---

## A

### ADR

Architecture Decision Record. A document that captures a significant architectural
or process decision that has been made. Records the context, the decision itself,
and its consequences. Stored in `docs/adr/`.

### AID

AI Interaction Document. A document that records a significant interaction with an
AI assistant during development. Captures the goal, what was done, the outcome,
and lessons learned. Stored in `docs/aid/`.

### AIR

Architecture Issue Record. A document that captures a conflict between two or more
ADRs that cannot be resolved by simply updating one of them. Stored in `docs/air/`.

### Archive

A subfolder (`archive/`) within AID directories that holds superseded or obsolete
documents. Archived documents are kept for historical reference and are not deleted.

## C

### Commitlint

A tool that validates commit message format against the rules defined in
`commitlint.config.mjs`. Runs as a `commit-msg` git hook via Lefthook.

### Conventional Commits

A specification for commit message format: `<type>(<scope>): <description>`.
Valid scopes for this project are defined in `commitlint.config.mjs` and listed
in `docs/context/project.md`.

## D

### Decisions log

The file `docs/context/decisions.md`. An append-only journal of decisions made
during the project — both ADR-level decisions and smaller choices that don't warrant
a full ADR. Newest entries are at the top. Never rewrite or delete existing entries.

### Dry-run

A mode in which all commands simulate their actions without making any changes
to the filesystem. Enabled per-command with `--dry-run`, or globally by setting
`DEBUG=true` in `.env`. Checked in services via `ConsoleCommandService::isDryRun()`.

## E

### EBU R128

A loudness normalisation standard used by Spotify, YouTube, and Apple Music.
Implemented in FFmpeg via the `loudnorm` filter in two-pass mode with a target
of -14 LUFS. See ADR-0001.

## F

### FFmpeg

The audio processing tool used for silence trimming, EBU R128 loudness normalisation,
and transcoding to MP3 (`libmp3lame`). Called directly via `symfony/process` — no
PHP wrapper package. Minimum required version: 4.4. See ADR-0001.

## G

### getID3

The sole metadata reading library (`james-heinrich/getid3`). All calls go through
`MusicMetadataService` — no other class uses getID3 directly. Read-only; never
modifies files. See ADR-0003.

### Git hook

A script that runs automatically at a specific point in the git workflow
(e.g. before a commit, before a push). Managed by Lefthook in this project.

## I

### INDEX.md

A file in each document directory (ADR, AIR, AID) that lists all documents
in that directory. Kept up to date after every new document is created.

## L

### Lefthook

A Git hooks manager configured in `lefthook.yml`. Runs PHP CS Fixer on pre-commit,
commitlint on commit-msg, and PHPStan + Pest on pre-push.

## P

### Project context

The folder `docs/context/`. Contains persistent project memory: core facts
(`project.md`), decision log (`decisions.md`), and session summaries (`sessions/`).
Read by AI assistants at the start of each session.

## R

### Release-it

A tool for automating versioning and changelog generation. Configuration is in
`.release-it.json`. Run via `npm run release:patch|minor|major`.

### RFC

Request for Comments. A document that captures an idea or proposal before a
decision is made. Used for parking ideas or flagging things that need a decision later.

## S

### Scope

The part of a commit message in parentheses that identifies what area of the project
was changed: `feat(service): add deduplication logic`. Project-specific scopes:
`command`, `service`, `exception`, `helpers`, `lang`, `config`, `docs`, `deps`.

### Session summary

A Markdown file written at the end of a long working session that summarises what
was done, what decisions were made, and what comes next. Stored in
`docs/context/sessions/` and named `YYYY-MM-DD-topic.md`.

### Silence trimming

Removal of silent audio segments from the beginning and end of a track using
FFmpeg's `silenceremove` filter. Part of the `audio:process` pipeline. See ADR-0001.
