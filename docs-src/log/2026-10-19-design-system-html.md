---
title: Design system on the docs site
description: The design system now renders to docs/design-system.html and has a card on the docs index.
date: 2026-10-19
pr: ~
plan: ~
---

# Design system on the docs site

## Asked
"can we make a html version of design system? probably link with the index
html so it has the entry"

## Done
- Docs: `docs-src/design-system.md` gained `audience: stakeholder`,
  `badges: Design` and `order: 40`, so `php docs-src/build.php` renders it to
  `docs/design-system.html` and lists it on `docs/index.html` after Booking
  Flow. No change to the build script; the markdown stays the only source.

## Not done
- No colour swatches or live component previews — the page is the plain
  render, like the PID and SRS. Swatches would repeat the hex values from
  `tokens.css` by hand.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `php docs-src/build.php` (in Docker): rendered the new page; `pid.html`
  and `srs.html` came out unchanged.
- Opened the page in headless Chromium at 1280px and 375px: tables render,
  no sideways scroll.
- No app code changed, so the test suites were not rerun.
