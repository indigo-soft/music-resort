# Session: 2026-05-18 — Phase 5 completion + v1.1.0 release

## What was done

### commitlint — finally working
Root cause found: pnpm v11 on WSL2 creates text redirect files instead of real
symlinks in node_modules. This breaks Node.js ESM resolution for all local packages.

Solution:
- `commitlint` installed globally via npm
- `commitlint.config.js` renamed to `commitlint.config.mjs` (Node v24 ESM requirement)
- `extends: ['@commitlint/config-conventional']` removed from config — all rules
  defined inline to avoid local node_modules resolution entirely
- lefthook `commit-msg` hook calls global `commitlint` directly

### release-it — working
Same pnpm symlink issue. Solution:
- `release-it` and `@release-it/conventional-changelog` installed globally via npm
- Removed from local `devDependencies`
- `"release-it": { "extends": "./scripts/.release-it.json" }` kept in `package.json`
  so global release-it reads project config
- `release.sh` updated to call `release-it` directly instead of `npx release-it`

### DI violation fixed (via Claude Code)
- `Mp3ResortService::processSingleFile()` no longer instantiates services directly
- `MusicMetadataServiceFactory` created — factory pattern for per-file metadata instances
- `FileResortService::moveToArtistFolder()` refactored — artist/title as method args,
  not constructor params
- `ResortMp3Helper::createResortService()` handles wiring (acceptable for coordinator)

### Tests
- `phpunit.xml` created — Pest v3 requires it
- `pest.php` fixed — correct test directory paths
- `composer test` script fixed — removed `-d` flag
- 14/14 tests passing (9 unit + 5 integration)

### v1.1.0 released
- GitHub Release published with full changelog
- Tag `v1.1.0` created

## Key learnings captured in docs.template

- `pnpm run init` not `pnpm setup` (setup is a pnpm built-in)
- `commitlint.config.mjs` required for Node v24
- No `extends` in commitlint config — inline rules only
- `release-it` must be global on WSL2+pnpm setups
- `"release-it"` section in package.json required even without local dep
- `commitlint` must be global on WSL2+pnpm setups

## Global tools required (WSL2 + pnpm v11)

```bash
npm install -g commitlint @commitlint/cli @commitlint/config-conventional
npm install -g release-it @release-it/conventional-changelog
```
