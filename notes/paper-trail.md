---
title: Paper Trail
description: The four record types kept after work is done, when each is written, and how a recap is built from them.
badges: Paper Trail
order: 55
---

# Paper Trail

Records written **after** work is done. Plans in `.claude/plans/` say what we
intended. These say what happened, what we decided, what we learnt, and what we
let in. They are the primary source when someone asks for a recap of past work.

All four live under `notes/` as markdown, one file per entry, and are
coder-facing: they are read in the repo and never rendered to `docs/`.
Each folder has a `_template.md` to copy. `ls` the folder for the index; the
file names carry the date or number.

| Collection | Folder | Written when | File name |
|---|---|---|---|
| Session log | `notes/log/` | Every completed task | `YYYY-MM-DD-{slug}.md` |
| Decision record (ADR) | `notes/adr/` | A decision meets the bar below | `NNNN-{slug}.md` |
| Knowledge entry | `notes/knowledge/` | We learnt something non-obvious | `{slug}.md` |
| Dependency intake | `notes/intake/` | Before adding any dependency | `{name}.md` |

## Session log

One entry per completed piece of work, written in the same PR as the work.
No log entry, the task is not done. It records what was asked, what was done,
what was left out, what other records it produced, and how it was verified.

A task too small to fill that template does not get an entry. A commit message
is enough for it.

**Recap rule.** To answer "what did we do", read `notes/log/` by date first.
Fall back to git history only for detail the log does not carry.

## Decision record

Written only when a decision is one of:

- **destructive** — data, files, or history are removed and cannot be recovered
- **irreversible** — undoing it later would cost more than a normal change
- **against convention** — it contradicts a rule in `CLAUDE.md` or an earlier ADR

Ordinary choices inside a feature do not get one. Numbered from `0001`; an ADR
is never edited after acceptance, it is superseded by a new one that links back.

## Knowledge entry

One fact per file. The bar: it was not obvious, it cost time to find, and it
will matter again. Most traps now in `CLAUDE.md` would qualify. If a
knowledge entry becomes a rule everyone must follow, promote a one-line summary
to `CLAUDE.md` and keep the entry as the long form.

Project facts go here, not in per-machine memory. Memory is for cross-project
user preferences only.

## Dependency intake

Written **before** a dependency is added, for anything the project would not
run, build, test, or deploy without: composer and bun packages, binaries,
Docker images, GitHub Actions, PHP extensions. Transitive dependencies do not
get their own entry; the direct one notes the size of the tree.

Every entry answers the same questions: why, and what we would do without it;
what it touches; maintenance state; security state; license; alternatives;
verdict. The maintenance and security sections are a snapshot and carry the
date they were checked. Ongoing coverage is `composer audit` and `bun audit`
in CI, not this file.

A removed dependency keeps its entry with `status: removed` and the reason, so
the trail still explains why it was ever there.
