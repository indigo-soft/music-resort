# AI Interaction Documents (AID)

AID documents capture significant interactions with AI systems (Claude, Copilot, GPT, etc.)
that produced a decision, artifact, or insight worth preserving.

Unlike ADRs (which document architectural decisions) and AIRs (which document conflicts
between ADRs), AIDs document **the AI collaboration process itself** — what was asked,
what was produced, how it was evaluated, and what was adopted or rejected.

AIDs serve as an audit trail of AI-assisted work in the project and a learning resource
for future interactions.

---

## When to create an AID

Create an AID when an AI interaction:

- Generated a significant artifact that was adopted (document, code, architecture, guide)
- Produced a decision that shaped the project direction
- Failed in an instructive way — the failure and correction are worth documenting
- Introduced a new prompting strategy worth repeating
- Touched multiple areas of the codebase or multiple documents at once

**Do not create an AID** for:

- Trivial autocomplete or minor edits
- One-line fixes or formatting suggestions
- Interactions whose output was entirely discarded with no learning

Use the prompt `docs/prompts/aid.md` to create a new AID.

---

## AID Lifecycle

| Status       | Meaning                                                    |
| ------------ | ---------------------------------------------------------- |
| **Draft**    | Interaction in progress or evaluation not yet complete     |
| **Accepted** | Output evaluated, adoption decision made, lessons captured |
| **Archived** | Superseded by a newer interaction or no longer relevant    |

---

## File naming

```text
AID-XXXX-short-description.md
```

- `XXXX` — zero-padded 4-digit number (`0001`, `0042`)
- `short-description` — kebab-case, English, 3–5 words describing the interaction topic

---

## Archiving

When an AID is no longer relevant or is superseded, move it to `archive/`:

1. Update its status to `Archived`
2. Move the file: `mv docs/aid/AID-XXXX-... docs/aid/archive/`
3. Update `INDEX.md`
