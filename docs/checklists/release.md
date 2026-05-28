# Release Checklist

> Combined checklist for human and AI assistant.
> AI executes each step and reports the result.
> Human confirms before moving to the next block.

---

## Block 1 — Pre-release check

_AI runs these checks and reports any issues._

- [ ] Working branch is `main` and is up to date with remote
- [ ] No uncommitted changes (`git status` is clean)
- [ ] CI is green on the latest commit
- [ ] `npm run release:dry` runs without errors — AI pastes the output for review

**→ Human confirms output looks correct before continuing.**

---

## Block 2 — Version decision

_Human decides, AI confirms the command to run._

- [ ] Release type selected: `patch` | `minor` | `major`
- [ ] CHANGELOG entries reflect what is actually shipping

**→ Human confirms version bump and changelog before AI proceeds.**

---

## Block 3 — Release

_AI runs the release command._

- [ ] `npm run release:<type>` executed
- [ ] Git tag created and pushed
- [ ] CHANGELOG.md updated
- [ ] `package.json` version bumped

**→ Human verifies the tag and changelog on GitHub before continuing.**

---

## Block 4 — Post-release

- [ ] GitHub Release created (manually or via release-it config)
- [ ] Release announced in the team channel (if applicable)
- [ ] `decisions.md` updated if any process decisions were made during the release

**→ Done. Human closes the checklist.**
