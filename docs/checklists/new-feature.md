# New Feature Checklist

> Combined checklist for human and AI assistant.
> AI sets up the structure, human reviews and confirms each block.

---

## Block 1 — Clarity

_Human answers, AI captures the answers._

- [ ] Feature goal stated in one sentence: **\_\_\_**
- [ ] Success criteria defined (how will we know it works?): **\_\_\_**
- [ ] Scope agreed: what is explicitly out of scope for this feature?

**→ Human confirms the goal before AI proceeds.**

---

## Block 2 — Decision check

_AI checks whether an ADR is needed._

- [ ] Does this feature introduce a significant architectural or process decision?
    - Yes → AI creates a draft ADR using `docs/prompts/adr.md` before any code is written
    - No → continue
- [ ] Is there an RFC already open for this feature? If yes, link it: **\_\_\_**

**→ Human confirms ADR is adequate (or not needed) before continuing.**

---

## Block 3 — Branch setup

_AI executes, human confirms._

- [ ] Branch name follows convention: `feature/XXXX-short-description`
- [ ] Branch created from latest `main`
- [ ] Branch pushed to remote

**→ Human confirms branch name and source before AI proceeds.**

---

## Block 4 — Scaffold

_AI creates empty files and stubs, human reviews._

- [ ] New files created in the correct locations
- [ ] Tests file(s) created alongside implementation files
- [ ] Documentation placeholder created if a new guide or section is needed

**→ Human reviews the scaffolded structure before implementation begins.**

---

## Block 5 — Definition of done

_Human fills in, both track during development._

- [ ] Implementation complete
- [ ] Tests written and passing
- [ ] Documentation updated
- [ ] ADR updated or `decisions.md` entry added (if decisions were made during development)
- [ ] PR opened with description filled in
- [ ] Code review checklist completed by reviewer (`docs/checklists/code-review.md`)
