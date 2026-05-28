# Architecture Issue Records (AIR)

AIR documents capture **conflicts between ADRs**: when a new decision contradicts an existing one,
or when two existing ADRs conflict when applied together.

Unlike ADRs, AIRs are always **temporary**. The goal of an AIR is to document the conflict,
analyse resolution paths, and reach a decision. Once the decision is made and reflected in the
affected ADRs, the AIR is considered resolved.

> **Looking for AI interaction records?** Those are [AID documents](../aid/README.md) —
> a separate document type for capturing significant AI interactions and their project impact.

Resolved AIRs stay in this directory with a `done-` prefix in the filename —
open ones always appear first in a sorted listing.

---

## Current AIRs

### Open

_No open AIRs._

### Resolved

_No resolved AIRs yet._

---

## When to create an AIR

Create an AIR when:

- A new ADR **directly contradicts** an existing one
- Two existing ADRs **conflict in practice** when applied together
- An external requirement contradicts the current architecture and the trade-off must be documented

**Do not create an AIR** when:

- One decision simply depends on another (use `Depends on:` in the ADR)
- One decision extends or complements another (use `Related to:`)
- The conflict can be resolved in a PR comment without a dedicated document

---

## AIR Lifecycle

| Status       | Meaning                                                                  |
| ------------ | ------------------------------------------------------------------------ |
| **Open**     | Conflict identified; resolution not yet decided — needs attention        |
| **Resolved** | Decision made, reflected in affected ADRs, file renamed to `done-`       |
| **Deferred** | Resolution deliberately postponed, with an explicit condition to revisit |

---

## File naming

```text
air-00N-short-conflict-description.md       ← open
done-air-00N-short-conflict-description.md  ← resolved
```

Use the prompt `docs/prompts/air.md` to create a new AIR.
