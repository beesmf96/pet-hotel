---
title: A local Playwright suite for the booking flow
description: `bun run e2e` runs the manual booking checklist (A–G, 22 checks) in Chromium against a throwaway e2e app with its own SQLite database; not in CI.
date: 2026-10-11
pr: ~
plan: plan-e2e-playwright
---

# A local Playwright suite for the booking flow

## Asked
"Is it possible to set up Playwright tests with the steps you showed me, to
verify? Do we have one?" There was none. The user chose local only ("keep CI
lean, adding Playwright is too heavy") and a separate database.

## Done
- Intake first: `docs-src/intake/playwright.md` (`@playwright/test` 1.64.0,
  dev, local only; also the downloaded Chromium build).
- `playwright.config.js` and `bun run e2e`: builds assets, starts a throwaway
  `app` container on port 8001 with `php -S` and environment overrides
  (`APP_ENV=e2e`, SQLite `database/e2e.sqlite`, sync queue, array cache,
  file sessions, Mailpit), runs Chromium with one worker.
- `AppServiceProvider`: under `APP_ENV=e2e`, Vite points at a hot file that
  never exists, so the e2e app serves `public/build` while `bun run dev` runs.
  The e2e app gets the production CSP (its Vite exception is `local` only).
- `E2eSeeder`: one hotel "E2E Paws Hotel" (capacity 2, 2:00 PM / 12:00 PM,
  dog RM 650, cat RM 45.50, no bird price), owner, admin and two guests with
  pets. It throws unless `APP_ENV` is `e2e`.
- `e2e/support/`: reset (checks the running app resolves to `e2e|sqlite|…e2e.sqlite`
  before `migrate:fresh`), `tinker` setup shortcuts, logins, calendar, Mailpit
  search limited to `@e2e.test`, owner-panel actions and the daily overview.
- Specs `a-` to `g-` follow the manual checklist: 22 checks.
- Vitest excludes `e2e/`; CI is unchanged.
- Found while writing it: the "Change dates" hint said "Leave empty to keep
  the normal capacity" on a field that is required unless Closed; it now
  says "Your normal capacity is N." And form labels on the customer site are
  not tied to their inputs — added to `docs-src/backlog.md`.
- Docs: `docs-src/local-setup.md` (how to run it), CLAUDE.md command list.

## Incident
While setting up, a command meant to reset the e2e database wiped and
reseeded the **dev** PostgreSQL database with the e2e data. The docker `-e`
flags were kept in a zsh variable, and zsh passed them as one argument, so no
override applied. The user chose not to restore the dev data. Two knowledge
entries record the traps (`zsh-does-not-split-unquoted-variables.md`,
`artisan-serve-drops-environment-variables.md`), CLAUDE.md gains a rule to
print the resolved database before any destructive artisan command, and the
suite now starts Docker with argument lists, checks the resolved database
before each reset, and has a seeder that refuses other environments.

## Not done
- No CI job, other browsers, or visual snapshots (by choice).
- Customer-site fields are found by input type until labels are linked.

## Produced
- ADR: none
- Knowledge: `zsh-does-not-split-unquoted-variables.md`,
  `artisan-serve-drops-environment-variables.md`
- Intake: `playwright.md`

## Verified
- `bun run e2e`: 22 passed, twice in a row (about 2 minutes each).
- Dev database (hotels, bookings, availability, users) and non-e2e Mailpit
  count identical before and after both runs.
- New `E2eSupportTest` (4): seeder guard, seeder data, the e2e Vite switch,
  default hot file elsewhere.
