---
title: Keep a paper trail in the repo after work is done
description: Session log, ADRs, knowledge entries, and dependency intake become part of the definition of done.
date: 2026-09-13
status: accepted
supersedes: ~
superseded_by: ~
---

# 0001. Keep a paper trail in the repo after work is done

## Context
Plans in `.claude/plans/` recorded what we intended to build. `docs/tasks.md`
recorded what shipped as checkboxes. Nothing recorded why a decision was made,
what was learnt along the way, or why a dependency was let in. A recap of past
sessions had to be rebuilt from git history and chat transcripts, and facts
learnt in one session were kept in per-machine memory that the repo does not
carry.

This changes the definition of done for every task, which is why it is an ADR
and not just a docs page.

## Decision
We will keep four record types under `docs/`, one file per entry, as
coder-facing markdown that is never rendered to the stakeholder HTML site:

- a session log entry for every completed task, in the same PR as the work
- an ADR for any decision that is destructive, irreversible, or against convention
- a knowledge entry for any non-obvious fact worth keeping
- a dependency intake entry before any package, binary, image, or action is added

The session log is the primary source for a recap. Project facts live in
knowledge entries, not in per-machine memory. The rules are in
`docs/paper-trail.md`.

## Alternatives
- One growing log file: no merge conflicts avoided, no stable links, unbounded
  size. Rejected.
- Log rolled by quarter: caps size but keeps conflicts inside a quarter.
  Rejected as a starting point.
- Per-machine memory only: not in git, not readable without the tool. Rejected
  for project facts.
- Rely on git history: noisy, and a decision cannot be read out of a diff.

## Consequences
- Markdown under `docs/` is now coder-facing by default; only pages marked
  `audience: stakeholder` render to `docs/html/`. Developer docs stay out of
  the site shown to users and stakeholders.
- Every PR carries at least one docs file. Small, but never zero.
- Reviewers can ask "where is the log entry" and reject a PR without one.
- The bar for ADR, knowledge, and intake must be held. If entries are written
  for ordinary work the folders become noise and stop being read.
- Promotion to a global convention is a separate, later decision.
