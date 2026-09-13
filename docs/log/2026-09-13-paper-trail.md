---
title: Paper trail discipline
description: Added the session log, ADR, knowledge, and intake record types, with a renderer and rules.
date: 2026-09-13
pr: 33
plan: .claude/plans/plan-paper-trail.md
---

# Paper trail discipline

## Asked
Shape a discipline that keeps a paper trail after work is done, to be the
primary source for a recap. Three ideas came from the user: a log, knowledge
entries for things learnt in a session, and ADRs for destructive or
against-convention decisions. A fourth, dependency intake, was added during
the discussion: justify each package or tool, and record its maintenance and
security state on the day. Apply to this project first, consider global later.

## Done
- Four folders under `docs/` (`log`, `adr`, `knowledge`, `intake`), each with
  a `_template.md`. They are coder-facing markdown and are not rendered.
- Split the docs by audience: markdown under `docs/` is coder-facing by
  default; only pages marked `audience: stakeholder` render, and they now
  render into a dedicated `docs/html/` folder with the hand-written pages and
  the stylesheet. Generated HTML for runbooks and the paper trail was removed.
- `docs/paper-trail.md` states the rules and the bar for each record type.
- `CLAUDE.md` names the log entry as part of the definition of done, and the
  intake entry as a step before adding a dependency.
- ADR 0001 records the decision itself.

## Not done
- No backfill of past work. Existing traps in `.claude/CLAUDE.md` stay where
  they are.
- No global version. Decided to trial the "when to write one" rule here first.
- No hook to enforce the log entry. Review does that for now.

## Produced
- ADR: ../adr/0001-adopt-paper-trail.md
- Knowledge: none
- Intake: none

## Verified
`php docs/build.php` renders the stakeholder pages only. `composer test` and
`vendor/bin/pint --test` run clean. See the PR for the actual output.
