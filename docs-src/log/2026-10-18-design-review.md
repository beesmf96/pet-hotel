---
title: First design review
description: The customer pages checked against the design system in code and in screenshots; 14 Fix and 9 Align findings fixed, with the new rules in the design doc.
date: 2026-10-18
pr: ~
plan: ~
---

# First design review

## Asked
`/design-review` over the customer pages. The user approved every Fix and
every Align finding.

## Done
- **Fix** (broke the doc):
  - sentence case on headings and buttons
  - "…" and busy labels on every submit button
  - Inertia `<Link>` between pages (Google sign-in and `/owner` stay plain `<a>`)
  - `link-bold` on text links
  - the `cream` button variant removed
  - `leading-tight` set in the AppLayout header
  - corners on the scale; `StatusPill` for the "pending" word on the confirmation page
  - the hotel page shows "2:00 PM", not "14:00" (new `times` prop from `HotelController`)
  - the notifications panel fits a phone
  - Sign in / Sign out and detail labels no longer wrap
  - the stay times moved inside the calendar card (new default slot)
  - the hotel page calendar now greys out past days
  - a styled photo field
- **Align** (new rules, written into `docs-src/design-system.md`):
  - sentence case for labels, nav links and chips
  - one `facilities.js` list instead of three different ones
  - one day-first date format on pages, emails and panels: `useFormatDate`
    is now the JS twin of `App\Support\Dates`, plus `formatDateTime()`
  - a new `coral-deep` token (#b93d14, 5.6:1 on white, 5.3:1 on cream) for
    coral text; coral itself is 2.3:1
  - calendar colours from the tokens
  - `FormField optional` replaces "*" on required fields
  - a second nav row on phones, from one link list
  - mustard as the main button on a teal card
  - thin dividers allowed inside cards
- The recapture found three side effects of the fixes; each was fixed:
  - the phone nav row cut off "Profile"
  - "Age in years (optional)" wrapped
  - the notifications panel covered the new nav row
- Doc section 9 is now empty.

## Not done
- **Notes, left as they are:**
  - `/dashboard` is an unlinked placeholder page.
  - Date inputs follow the browser's language ("mm/dd/yyyy" in US English).
  - The empty Pets page has two "add" buttons.
- **Seen during the fixes, for the next review:**
  - The policy text on the hotel page uses `text-[15px]`, which is off the type scale.
  - On the confirmation page, long values (check-in, check-out) wrap
    left-aligned while short ones sit on the right.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `bun run test`: 338 passed.
- `bun run lint`: 0 errors (the same 5 old warnings).
- `composer test`: 550 passed. Pint: pass.
- `bun run design:capture`: 8 passed. I checked the changed pages at phone
  width after the fixes, then again after the three side-effect fixes.
