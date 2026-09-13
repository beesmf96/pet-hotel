---
title: Consolidate CLAUDE.md
description: Folded .claude/CLAUDE.md into the root CLAUDE.md, keeping only rules that cannot be inferred from the code.
date: 2026-09-13
pr: 35
plan: ~
---

# Consolidate CLAUDE.md

## Asked
The split between `CLAUDE.md` and `.claude/CLAUDE.md` dated from the dropped
agent pipeline. Judge which details are generic framework knowledge and which
need special treatment, then update the root file and keep it short.

## Done
- One `CLAUDE.md` at the root, 123 lines. `.claude/CLAUDE.md` removed.
- Kept: stack line, compact domain model, commands, Docker `--user appuser`,
  hosts file, queue worker warning, the nine project traps, project-specific
  test conventions, definition of done, docs audience rule, branch flow, CI gate.
- Dropped as generic or derivable: exact version table, Inertia `useForm` vs
  `router.*` guidance, `Queue::fake()` reminder, the separate Leaflet/Redis
  notes, the "Not yet built" section (folded into the stack paragraph).
- Docs that pointed at `.claude/CLAUDE.md` (`paper-trail.md`, `pid.md`,
  `srs.md`) now point at `CLAUDE.md`; stakeholder HTML rebuilt.

## Not done
- Older plans under `.claude/plans/` still mention `.claude/CLAUDE.md`. They
  are historical and were left as written.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
Docs-only change. `grep` for `.claude/CLAUDE.md` finds no hits outside old
plans and this log. `php docs/build.php` ran clean in the app container.
