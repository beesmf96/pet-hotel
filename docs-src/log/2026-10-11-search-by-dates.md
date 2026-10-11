---
title: Search by stay dates
description: Check-in and check-out in the search bar now hide hotels that are full or closed on any night of the stay; closes SRS OI-6.
date: 2026-10-11
pr: ~
plan: ~
---

# Search by stay dates

## Asked
SRS OI-6, picked by the user from the list of open work: the search bar's
dates were passed through and filtered nothing.

## Done
- Backend: with both dates valid and check-out after check-in, search drops
  hotels that cannot take one more pet on every night.
  `Availability::unavailableHotelIds()` checks only the hotels that could
  fail — those with a date override or a booking holding a spot in the
  range, or with no capacity — with the same `fits()` the booking form uses,
  and the search query excludes them, so pagination still works. One date,
  reversed dates, the same date twice or an impossible date (30 Feb) filter
  nothing, as before.
- The page gets a `stay` prop. The results line adds "With a spot every night
  from Oct 25, 2026 to Oct 28, 2026 (3 nights)", and an empty result says
  "No hotels with space" instead of "No hotels found". The check-out field
  cannot be set before check-in.
- Docs: SRS FR-12 rewritten (it also wrongly said dates were carried to the
  booking form), OI-6 closed; user guide search table.

## Not done
- The dates are still not carried from a search result to the hotel page or
  the booking form; the customer picks them again on the calendar.
- No "spots left" shown on the hotel cards.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `HotelSearchTest`: full and closed nights hide a hotel; a problem night
  outside the stay (before it, or on the check-out day), a pending request
  and a raised night capacity do not; no capacity hides it; six kinds of
  incomplete or invalid dates filter nothing. Vitest: the summary (plural and
  singular), no summary without a stay, the "no space" empty state, and the
  check-out `min`. E2e C7 now searches the full night (no hotels) and the
  next night (hotel listed).
- PHPUnit: 512 passed, 1857 assertions. Coverage 98.48% of lines. Pint clean.
  `bun run test`: 290 passed. `bun run lint`: 0 errors, the same 5 existing
  warnings. `bun run e2e`: 22 passed.
