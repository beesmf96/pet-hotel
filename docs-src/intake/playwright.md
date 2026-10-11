---
title: "@playwright/test"
description: Browser test runner for the local end-to-end suite that clicks through the booking flow.
date: 2026-10-11
status: accepted
kind: bun
scope: dev
source: https://github.com/microsoft/playwright
license: Apache-2.0
version: 1.64.0
---

# @playwright/test

Also covers the **Chromium build** that `playwright install chromium` downloads
to `~/.cache/ms-playwright` (a binary, not in the repository).

## Why we need it
The user asked to automate the manual booking-flow checklist (owner settings
and availability, calendar, requests and confirm, cancel and decline emails,
prices, the two notification bells). PHPUnit drives Filament through Livewire
and Vitest mounts single Vue components; neither runs the real pages in a
browser, follows Inertia navigation, or reads the emails in Mailpit. Without
it the checklist stays manual and is skipped under time pressure.

Local only. The user chose to keep CI lean, so nothing in
`.github/workflows/ci.yml` installs or runs it.

## Maintenance (checked 2026-10-11)
- Last release: 2026-10-07, 1.64.0
- Release cadence: a minor release every 4–8 weeks (1.56.0 in 2025-10 to
  1.64.0 in 2026-10), daily alpha builds in between
- Maintainers: Microsoft, a full-time team
- Issues: high volume, triaged and answered by the team

## Security (checked 2026-10-11)
- Known CVEs or advisories: GHSA-7mvr-c777-76hp (browser download skipped TLS
  certificate checks), fixed in 1.55.1. None open for 1.64.0.
- Time to fix past advisories: patched in a point release
- Transitive dependencies: `playwright` → `playwright-core`, which has no
  dependencies. Optional `fsevents` on macOS only.
- Where checked: osv.dev (`playwright`, `playwright-core`, `@playwright/test`),
  npm registry

## Alternatives
- Laravel Dusk: PHP and ChromeDriver, fits Laravel, but slower, needs a
  ChromeDriver kept in step with Chrome, and is weaker at waiting on
  Inertia's client-side navigation.
- Cypress: heavier install, a separate app runner, and cross-origin limits
  that make reading Mailpit awkward.
- Pest browser testing: would add Pest beside PHPUnit 12 for one suite.

## Verdict
Accepted, dev only and local only: the best fit for an Inertia SPA, with no
transitive dependencies and an active maintainer.
