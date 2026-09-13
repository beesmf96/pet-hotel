---
title: Intake entries for gitleaks
description: Backfilled the dependency intake for the gitleaks scanner and the gitleaks GitHub Action.
date: 2026-09-13
pr: 36
plan: ~
---

# Intake entries for gitleaks

## Asked
Write an intake entry for gitleaks, the first real use of the intake template.

## Done
- `docs/intake/gitleaks.md` covers the scanner used by the pre-commit hook
  (local binary or the pinned Docker image).
- `docs/intake/gitleaks-action.md` covers the CI Action separately, because
  its licence is a proprietary EULA rather than MIT and its maintenance
  picture is thinner.
- Facts gathered from the GitHub API, osv.dev, the Docker Hub tag list, and
  the upstream README and release notes. All dated in the entries.

## Not done
- The CI workflow was **not** changed. The intake found that
  `gitleaks/gitleaks-action@v2` stops working on 2026-09-16 when GitHub
  removes Node 20 from hosted runners. Bumping to `v3` is a one-line change
  and needs doing before then. Left for a separate PR so the intake stays a
  record and not a code change.
- No digest pin on the Docker image, no minimum version for the local binary.
  Both noted as follow-ups in the entry.

## Produced
- ADR: none
- Knowledge: ../knowledge/pre-commit-hook-fails-in-worktrees.md
- Intake: ../intake/gitleaks.md, ../intake/gitleaks-action.md

## Verified
Docs only. No renderer run needed: intake entries are coder-facing markdown.
