# Session: 2026-05-15 — Testing docs.template on music.local + Phase 5 tooling

## What was done

### music.local — all docs generated
- `docs/context/project.md`, `decisions.md` — project memory
- `docs/checklists/new-project.md` — phases 1-4 complete
- `AGENTS.md` — rewritten to template structure
- `docs/architecture/` — overview, components, modules
- `docs/guides/` — coding-standards, deployment, testing
- `README.md`, `CHANGELOG.md`, `SECURITY.md`, `ROADMAP.md`
- `tests/Unit/`, `tests/Integration/` — stub tests created
- `package.json`, `lefthook.yml`, `commitlint.config.mjs` — Node.js tooling

### docs.template — prompts improved throughout the session
All prompts updated based on real test findings:
- `onboarding.md` — reads existing files first, creates Node.js tooling
- `agents.md` — reads existing AGENTS.md, preserves project-specific sections
- `architecture.md` — source code inspection before asking questions
- `coding-standards.md` — reads linter configs before asking
- `deployment.md` — reads .env.example and package scripts
- `testing.md` — creates directory structure + functional stubs

### lefthook.yml — fail_text added to all hooks (visible red errors)

## Blocked: commitlint in git hook context (Phase 5)

**Problem:** `node_modules/@commitlint/cli/cli.js` is not found when lefthook
runs the commit-msg hook, even though it works from the terminal.

**Root cause (Claude Code diagnosis):** pnpm on this WSL2 setup creates
text redirect-files instead of real symlinks when `node-linker=hoisted` is set.
Without it, symlinks are correct but Node can't resolve them in hook context.

**What was tried:**
- `pnpm exec commitlint` — EACCES / RECURSIVE_EXEC_FIRST_FAIL
- `node node_modules/@commitlint/cli/cli.js` — Cannot find module
- `.pnpm` store glob path — ERR_MODULE_NOT_FOUND (@commitlint/lint)
- `shamefully-hoist=true` — cli is a redirect file, not a directory
- `node-linker=hoisted` — redirect files, not real dirs
- Wrapper script `.lefthook/commitlint.sh` — same underlying issue
- `commitlint.config.mjs` rename (official Node v24 fix) — still failing

**Current state:**
- `commitlint.config.mjs` ✅ (renamed from .js per Node v24 docs)
- `.npmrc` removed (back to pnpm defaults)
- `.lefthook/commitlint.sh` — uses `./node_modules/.bin/commitlint`
- `lefthook.yml` — `sh .lefthook/commitlint.sh {1}`

## Next session TODO

1. Finish debugging commitlint in hook context:
   - Check `cat node_modules/.bin/commitlint` — text redirect or real script?
   - Try global commitlint install as fallback
   - Try `npx --no-install commitlint` approach

2. After commitlint works — run `npm run release:dry`

3. Update docs.template:
   - Rename `commitlint.config.js` → `commitlint.config.mjs`
   - Update onboarding prompt accordingly
   - Document the pnpm + WSL2 + lefthook quirk in decisions.md
