---
plan: paper-trail
status: implemented
branch: feature/paper-trail
pr: ~
implemented: 2026-09-13
---

# Feature: Paper Trail

## What & Why
A discipline for records written after work is done: a session log, decision
records, knowledge entries, and dependency intake. Plans already capture
intent; nothing captured outcome, so a recap of past work had to be rebuilt
from git history and chat. Applied to this project first; promotion to a global
convention is a later decision.

## Scope
- Four folders under `docs/` with a `_template.md` each
- `docs/build.php` renders each collection to per-entry pages plus an index
- `docs/paper-trail.md` states the rules and the bar for each record type
- `CLAUDE.md` gains the "log entry or not done" rule and the intake rule
- First real entries: ADR 0001 and the log entry for this task

## Out of Scope
- Backfilling log, knowledge, or intake entries for past work
- Moving traps from `.claude/CLAUDE.md` into knowledge entries
- A global (cross-project) version of the discipline
- Automation (hooks) that enforce the log entry

## Technical Approach

### Backend
None.

### Frontend
None. Docs only.

## Acceptance Criteria
- [x] `php docs/build.php` renders the four collections and their indexes
- [x] Templates are skipped by the renderer
- [x] Rules page explains when each record is written
- [x] `CLAUDE.md` names the rule

## Edge Cases
- Empty collection renders an index with "No entries yet."
- Two entries on the same date sort by slug.

## Open Questions
None.
