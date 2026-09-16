---
title: Flip docs/ and docs-src/ for GitHub Pages
description: docs/ is now the published HTML site and all markdown moved to docs-src/, because Pages serves main:/docs and was rendering the paper trail.
date: 2026-09-16
pr: 41
plan: ~
---

# Flip docs/ and docs-src/ for GitHub Pages

## Asked
GitHub Pages is configured to serve `main:/docs`. That folder held the
coder-facing markdown plus a `html/` subfolder, so the site root had no
index and Jekyll rendered the log, ADRs, intake, and knowledge entries as
public pages. Flip it: HTML at `docs/` root, markdown out.

## Done
- `docs/html/*` moved up to `docs/`. `docs/.nojekyll` added so Pages serves
  the files as-is and processes nothing else.
- Every markdown file and `build.php` moved to a new `docs-src/` folder,
  keeping the `adr/`, `intake/`, `knowledge/`, `log/` layout. It was first
  named `notes/`; renamed to `docs-src/` on review because the name says
  what the folder is: the source the site is built from.
- `docs-src/build.php` reads `docs-src/*.md` and writes to `docs/`. Comments and
  output messages updated; logic unchanged.
- Path references updated in `CLAUDE.md`, `README.md` (now links to the
  live site), `docs-src/paper-trail.md`, `docs-src/pid.md`. The `docs/*.html`
  paths in `pid.md` and `srs.md` are now literally correct.
- Rendered output is byte-identical to the moved files except `pid.html`,
  which carries the corrected paths.

## Not done
- ADR 0001 and the 2026-09-13 log entries still say `docs/`. They are
  records of their day and were left as written.
- Pages settings on GitHub were not touched. `main:/docs` is still right;
  the site fixes itself on the next release to `main`.
- Old plans under `.claude/plans/` still say `docs/`. Historical.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
`php docs-src/build.php` in the app container rendered 3 pages and the index
(6 docs). `git diff -M docs` showed only the `pid.html` path text changed.
`vendor/bin/pint --test docs-src/build.php` passed. No app code changed, so
the PHPUnit and Vitest suites were not rerun.
