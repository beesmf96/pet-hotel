---
plan: e2e-playwright
status: implemented
branch: feature/e2e-playwright
pr: 64
implemented: 2026-10-11
---

# Feature: Local End-to-End Suite (Playwright)

## What & Why

The user asked to automate the manual checklist written after the booking
review (sections A–G: owner settings and availability, calendar and form,
requests and confirm, cancel and decline emails, capacity, prices, the two
bells). PHPUnit and Vitest test pieces; nothing runs the real pages in a
browser end to end. Intake: `docs-src/intake/playwright.md`.

## Scope

- `@playwright/test`, Chromium only, run with `bun run e2e`.
- **Local only** — not in CI (user decision: keep CI lean).
- **Separate e2e app and database** — never touches dev data.
- One spec per checklist section, with deterministic seed data.

## Out of Scope

- CI, other browsers, visual snapshots, Google OAuth.

## Technical Approach

- **Server.** Playwright's `webServer` starts a throwaway container from the
  `app` service: `docker compose run --rm --no-deps -p 8001:8001` with
  `php artisan serve`. Environment variables override `.env`:
  `APP_ENV=e2e`, `APP_URL=http://localhost:8001`, SQLite at
  `database/e2e.sqlite`, `QUEUE_CONNECTION=sync` (mail goes out in the
  request), `CACHE_STORE=array` (no rate-limit carry-over), file sessions,
  mail to the stack's Mailpit.
- **Assets.** `APP_ENV=e2e` makes `AppServiceProvider` point Vite at a hot
  file that never exists, so the e2e app serves `public/build` even while
  `bun run dev` is up. Global setup runs `bun run build`. The CSP stays as
  strict as production (its Vite exception is `local` only).
- **Data.** `E2eSeeder`: one hotel "E2E Paws" (capacity 2, 2:00 PM / 12:00
  PM, dog and cat priced, no bird price), its owner, an admin, two customers
  with pets. Each spec resets with `migrate:fresh --seeder=E2eSeeder` inside
  the running e2e container, so specs do not depend on each other.
- **Mail.** Specs read Mailpit's API and filter by the e2e addresses
  (`@e2e.test`); dev mail in the same Mailpit is left alone.
- **Helpers.** `e2e/support/`: reset, login per role, dates, Mailpit.

## Acceptance Criteria
- [x] `bun run e2e` runs A–G against the e2e app and passes
- [x] Running it does not change the dev database or dev mail
- [x] Works while the Vite dev server is running
- [x] CI unchanged; Vitest and ESLint ignore `e2e/`
- [x] Setup steps documented in `docs-src/local-setup.md`

## Edge Cases
- Port 8001 busy: the run fails fast with Playwright's webServer error.
- Chromium missing system libraries on WSL: documented one-time
  `install-deps` step.
