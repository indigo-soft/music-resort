# Code Review Checklist

> Quick checklist before approving a pull request.
> This is a reminder, not a replacement for `docs/guides/code-review.md`.

## Before you start

- [ ] I understand what this PR is trying to do
- [ ] I have read the PR description

## Correctness

- [ ] The code does what the PR description says
- [ ] Edge cases are handled or explicitly acknowledged
- [ ] No obvious bugs or logic errors

## Quality

- [ ] Names are clear and consistent with the rest of the codebase
- [ ] Complex logic has a comment explaining _why_, not just _what_
- [ ] No dead code, commented-out blocks, or debug leftovers

## Tests

- [ ] New behaviour has tests
- [ ] Existing tests pass (CI is green)
- [ ] Tests cover the important cases, not just the happy path

## Documentation

- [ ] Changed behaviour is reflected in the relevant docs
- [ ] Significant decisions are captured in an ADR or `decisions.md`

## Before approving

- [ ] My blocking comments are resolved or acknowledged
- [ ] Non-blocking suggestions are clearly marked as such (nit:, optional:)
- [ ] I would be comfortable maintaining this code
