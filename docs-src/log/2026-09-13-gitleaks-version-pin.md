---
title: Pin the gitleaks version in CI to match the hook
description: CI ran gitleaks 8.24.3 while the local hook ran 8.30.1; both now run 8.30.1.
date: 2026-09-13
pr: 39
plan: ~
---

# Pin the gitleaks version in CI to match the hook

## Asked
Follow-up from PR #38: set the gitleaks version the Action installs so CI
and the pre-commit hook scan with the same rule set.

## Done
- `.github/workflows/ci.yml`: `GITLEAKS_VERSION: '8.30.1'` on the scan step,
  matching the Docker image tag in `.githooks/pre-commit`.
- A comment in each file points at the other so the two are bumped together.
- `docs/intake/gitleaks-action.md`: version-drift note marked handled.

## Not done
- No automated check that the two versions agree. A comment is the guard.

## Produced
- ADR: none
- Knowledge: none
- Intake: ../intake/gitleaks-action.md (updated)

## Verified
CI run on the PR; the scan step log shows the version it installed.
