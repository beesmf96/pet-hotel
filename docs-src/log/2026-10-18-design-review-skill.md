---
title: Design review skill
description: A /design-review skill that checks the customer pages against the design system doc, in code and in screenshots of every page state, and fixes approved findings with tests.
date: 2026-10-18
pr: 82
plan: ~
---

# Design review skill

## Asked
Build `/design-review`: a QA / designer walk through the pages that keeps the
design consistent, using `docs-src/design-system.md` as the reference.

## Done
- `.claude/skills/design-review/SKILL.md`. The steps:
  - branch
  - code check against the doc
  - visual walk over the screenshots
  - report with Fix / Align / Note, then stop for approval
  - fix one item at a time with tests (an approved Align also adds its rule
    to the design doc)
  - full checks, then recapture and look again
  - log entry and PR to `dev`
  It runs only when the user types `/design-review`.
- `bun run design:capture` (`playwright.design.config.js`,
  `e2e/design/capture.spec.js`). It screenshots 24 page states at 375px and
  1280px into `storage/design-review/` (git-ignored). It reuses the e2e
  throwaway server and database. `bun run e2e` ignores `e2e/design/`.
- `DesignReviewSeeder` adds data on top of the personas: three reviews, a
  confirmed and a cancelled stay for Aisyah, read and unread notifications,
  and an empty account (`nina@example.com`). The text is fixed so
  screenshots compare from run to run. `resetDatabase()` takes the seeder name.
- CLAUDE.md and `local-setup.md` list the new command.

## Not done
- No review was run yet. The trial capture already shows work for the
  first run:
  - Title Case headings and buttons
  - pages print dates as "Sun, Nov 1, 2026" while emails and panels use
    "Sun, 01 Nov 2026"
  - the hotel's check-in and check-out times show as "14:00" on the hotel
    page and "2:00 PM" elsewhere
- No visual-regression baseline (`toHaveScreenshot`). Add it later if drift
  between reviews becomes a problem.
- The Filament panels are not captured.

## Produced
- ADR: none
- Knowledge: none
- Intake: none (Playwright was already a dependency)

## Verified
- `bun run design:capture`: 8 passed, 48 screenshots. Spot-checked the
  booking detail (desktop), the review modal (phone) and the hotel page (desktop).
- `composer test`: 550 passed. Pint: pass.
- `bun run test`: 323 passed.
- `bun run lint`: 0 errors (the same 5 old warnings).
