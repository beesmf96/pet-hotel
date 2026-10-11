---
title: Search dates carry to the booking form
description: Dates searched for travel from the results to the hotel page and pre-fill the booking form.
date: 2026-10-11
pr: ~
plan: ~
---

# Search dates carry to the booking form

## Asked
Follow-up to search by dates: a customer who searched for 25–28 Oct had to
pick the dates again on the booking form.

## Done
- `App\Support\Stay` reads `check_in` / `check_out` from the query string
  (both valid, check-out after check-in). Search uses it for its filter; the
  hotel page and the booking form pass on an upcoming stay as a `stay` prop.
- Hotel cards and the hotel page's **Book Now** carry the dates in the link
  (`resources/js/stay.js`). The hotel page shows "For Oct 25, 2026 to Oct 28,
  2026 (3 nights)" under Book Now, and its calendar opens on that month.
- The booking form starts with the dates filled in, the calendar opens on
  the check-in month with the stay selected, and the price summary shows as
  soon as a pet is picked. The customer can still change the dates.
- Past, reversed or invalid dates are dropped on the hotel page and the form.
- Docs: SRS FR-12, user guide search table.

## Not done
- The pet type searched for is not carried to the form's pet choice.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- PHPUnit: hotel page passes upcoming dates and drops missing or past ones;
  booking form passes dates and drops reversed ones. Vitest: `stayQuery`,
  card links with and without dates, Book Now link and the dates line
  (singular and plural), form starts empty or with the dates, calendar opens
  on this month or the check-in's month. E2e B6: search → hotel → Book Now →
  both dates selected and "RM 650.00 × 2 nights".
- PHPUnit: 515 passed, 1897 assertions. Coverage 98.49% of lines. Pint clean.
  `bun run test`: 301 passed. `bun run lint`: 0 errors, the same 5 existing
  warnings. `bun run e2e`: 23 passed (one earlier run had A5 fail once on an
  owner-panel dialog it does not touch; spec A then passed three runs in a row
  and the full suite passed again).
