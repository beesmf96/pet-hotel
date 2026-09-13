---
title: Bump gitleaks-action to v3
description: Moved the CI secret scan off Node 20 before GitHub removes it from hosted runners on 2026-09-16.
date: 2026-09-13
pr: 38
plan: ~
---

# Bump gitleaks-action to v3

## Asked
Bump the gitleaks GitHub Action to v3 and update its intake entry. Follows
the finding in the intake written earlier today.

## Done
- `.github/workflows/ci.yml`: `gitleaks/gitleaks-action@v2` to `@v3`.
  Runtime change only, Node 20 to Node 24; no input or behaviour change.
- Same file: `actions/checkout@v5` to `@v7` in all three jobs. Upstream
  suggests v6 as the Node 24 release; v7 is the current major and its only
  behavioural change (blocking fork checkouts under `pull_request_target`)
  does not apply to this workflow, which triggers on `pull_request`.
- `docs/intake/gitleaks-action.md`: version, deadline note, and verdict
  updated to say the bump is done.

## Not done
- Nothing. The licence-key condition for organization accounts still stands
  and is unchanged.

## Produced
- ADR: none
- Knowledge: none
- Intake: ../intake/gitleaks-action.md (updated)

## Verified
Workflow change only; the proof is the CI run on the PR. No PHP or frontend
code changed, so the local test suite was not rerun.
