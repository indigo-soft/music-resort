# New Project Checklist

> Master status for setting up a new project from this template.
> Claude updates this file after completing each step.
> Run prompts in `docs/prompts/` to complete unchecked items.

## Phase 1 — Foundation

- [x] **Onboarding** — core project facts collected and saved to `docs/context/project.md`
- [x] **Commit scopes** — `commitlint.config.mjs` created with project-specific scopes
- [x] **AGENTS.md** — generated and reflects actual project state

## Phase 2 — Architecture

- [x] **Architecture overview** — `docs/architecture/overview.md`
- [x] **Components** — `docs/architecture/components.md`
- [x] **Modules** — `docs/architecture/modules.md`

## Phase 3 — Guides

- [x] **Coding standards** — `docs/guides/coding-standards.md`
- [x] **Deployment guide** — `docs/guides/deployment.md`
- [x] **Testing guide** — `docs/guides/testing.md`

## Phase 4 — Project meta

- [x] **README.md** — rewritten with docs.template structure and links to all docs
- [x] **CHANGELOG.md** — initialized for release-it
- [x] **SECURITY.md** — security policy and reporting process
- [x] **ROADMAP.md** — v1.0.0 MVP + v1.1.0 test coverage + v1.2.0 CI/CD + backlog

## Phase 5 — Tooling verification

- [x] **package.json** — created with Node.js dev dependencies
- [x] **commitlint.config.mjs** — created with project scopes (no extends, all rules inline)
- [x] **lefthook.yml** — created with PHP-specific hooks + visible fail_text
- [x] **Lefthook installed** — `pnpm run init` works correctly
- [x] **commitlint working** — invalid commits rejected, valid commits pass
- [x] **Tests passing** — 14/14 unit + integration tests pass
- [x] **release:dry working** — all pre-release checks pass, release-it shows correct plan
- [ ] **First release** — `npm run release:minor` → tag v1.0.0

---

## Notes

pnpm v11 on WSL2 creates text redirect files instead of real symlinks.
Workaround: commitlint and release-it installed globally via npm.
lefthook works via `pnpm exec lefthook` (Go binary, no Node deps).
commitlint.config uses no `extends` — all rules defined inline to avoid
local node_modules resolution issues.
