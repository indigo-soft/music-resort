# New Project Checklist

> Master status for setting up a new project from this template.
> Claude updates this file after completing each step.
> Run prompts in `docs/prompts/` to complete unchecked items.

## Phase 1 — Foundation

- [x] **Onboarding** — core project facts collected and saved to `docs/context/project.md`
- [x] **Commit scopes** — `commitlint.config.js` created with project-specific scopes
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
- [x] **ROADMAP.md** — v1.0.0 MVP + v1.1.0 DI fix + v1.2.0 CI/CD + backlog

## Phase 5 — Tooling verification

- [x] **package.json** — created with Node.js dev dependencies
- [x] **commitlint.config.js** — created with project scopes
- [x] **lefthook.yml** — created with PHP-specific hooks
- [ ] **Lefthook installed** — run `pnpm install && pnpm prepare`
- [ ] **commitlint working** — test commit rejected if format invalid
- [ ] **release scripts working** — `pnpm release:dry` runs without errors
- [ ] **First release** — `pnpm release:minor` → tag v1.0.0

---

## Notes

All docs generated from source code inspection — minimal questions needed.
Node.js tooling added during test — onboarding prompt updated in docs.template.
Remaining open items require manual verification in terminal.
