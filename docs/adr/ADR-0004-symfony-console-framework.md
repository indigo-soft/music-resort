# ADR-0004: Symfony Console as the CLI Framework

## Status

Accepted — 2026-05-09

## Context

The project is a CLI tool. It needs argument/option parsing, progress bars, coloured output, dry-run support, and an
extensible command structure. The framework choice affects every command in the codebase and the integration with
parallel processing (see ADR-0005).

## Considered Alternatives

### Native PHP (argc/argv)

**Pros:**
- Zero dependencies
- Full control

**Cons:**
- All argument parsing, validation, help generation, and output formatting must be built by hand
- Progress bars, coloured output, and structured error messages require significant boilerplate
- Hard to scale when the number of commands grows

### Laravel Zero

**Pros:**
- Rich feature set (tasks, notifications, menus)
- Wraps Symfony Console with a higher-level API

**Cons:**
- Pulls in a large subset of Laravel — excessive for a focused CLI tool
- Opinionated project structure conflicts with the project's simple layout
- Heavier Composer dependency tree

### Native Symfony Console (chosen)

Selected. Part of the broader Symfony ecosystem already used in the project.

## Decision

Use **Symfony Console** (`symfony/console`) together with the supporting components:

| Component              | Purpose                                                   |
|------------------------|-----------------------------------------------------------|
| `symfony/console`      | Command structure, argument/option parsing, output styles |
| `symfony/finder`       | Recursive file discovery with filter/exclude support      |
| `symfony/filesystem`   | Cross-platform file move, rename, mkdir, remove           |
| `symfony/process`      | Child process spawning for parallel resort workers        |
| `symfony/string`       | Unicode-safe string operations (translit, slugify)        |

## Rationale

**Proven primitives.** `SymfonyStyle` provides progress bars, info/warning/error blocks, and section headers with a
single import. These replace hundreds of lines of custom output code.

**Finder and Filesystem.** Symfony Finder handles recursive file discovery with include/exclude patterns out of the
box. Filesystem abstracts `rename()`, `mkdir()`, and `remove()` with cross-platform correctness and atomic moves.

**Process Component.** Used for parallel worker spawning in `music:resort --concurrency=N` (ADR-0005). The same
package that runs workers also runs FFmpeg commands (ADR-0001), so no extra dependency is added.

**Incremental adoption.** Each Symfony component is independent and versioned separately. The project uses only what
it needs; there is no framework bootstrap overhead.

**PHPStan compatibility.** All Symfony components ship with complete type declarations, which is required to maintain
PHPStan level 8 analysis in the future.

## Consequences

### Positive

- Argument/option parsing, help generation, and shell completion come for free
- `SymfonyStyle` covers all output needs (progress, notes, warnings, success blocks) without custom code
- All five components share one Composer dependency tree with no conflicts
- `#[AsCommand]` attribute keeps command metadata co-located with the class

### Negative

- Symfony Console has a non-trivial learning curve for contributors unfamiliar with it
- `ArrayInput` used in `RunAllCommand` for sub-command invocation requires exact knowledge of each command's
  argument/option names — a breaking change in any command signature must be reflected there

### Neutral

- `symfony/dependency-injection` is deliberately excluded (see ADR-0002); the project does not use the full
  Symfony framework
- Commands are registered manually in `bin/console`; there is no auto-discovery
