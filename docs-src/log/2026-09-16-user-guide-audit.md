---
title: User guide audit
description: Checked docs/user_guide.html line by line against the code, fixed the stale parts, and corrected the admin bookings currency from USD to MYR.
date: 2026-09-16
pr: ~
plan: ~
---

# User guide audit

## Asked
"audit our user guide html see if it is still as accurate as per our source",
then "update the guide and fix the USD label too".

## Done
- Docs: `docs/user_guide.html` (a hand-written page in `STATIC_DOCS`, not
  rendered from markdown) was compared against routes, controllers, form
  requests, Vue pages, and both Filament panels. Wrong claims fixed: panel
  URLs are `/admin` and `/owner` (the subdomains were dropped long ago), no
  Distance sort in the UI, pet species is free text, "Leave a review" sits on
  the My Bookings list and opens a modal with an optional comment, calendar
  legend is Available / Limited / Full / Unavailable, the widget is named
  "Check-ins Today", owners register on the customer site before an admin
  attaches them, and any attached account (including an admin) can enter the
  owner panel. Gaps filled: Google sign-in, change or set password on the
  profile page, search-bar dates, hotel map, the "request received"
  notification and email delivery, the `/dashboard` landing page, and the
  admin View actions and filters.
- Backend: the admin bookings table formatted `total_price` as USD while the
  owner panel and pricing tab use MYR. Changed to MYR.
- Tests: one new Filament test asserts the admin bookings table renders the
  price as `MYR 150.00` (the formatter emits a non-breaking space) and not
  as dollars.

## Not done
- No screenshots were retaken; the guide has none.
- The guide still says nothing about admin bulk delete on bookings and
  photos beyond what was already there; those were judged fine as-is.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `composer test` in the app container: 374 passed, 1300 assertions.
- `vendor/bin/pint --dirty`: clean.
- No frontend files changed, so ESLint was not run.
