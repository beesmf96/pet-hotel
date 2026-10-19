---
title: Design loose ends
description: Keyboard focus on the rating stars, long text at text-base, /dashboard redirects to My bookings, and doc rules for the native date picker.
date: 2026-10-19
pr: ~
plan: ~
---

# Design loose ends

## Asked
"can we do 1/2 together": the rating-star focus item from the backlog, and
the three notes left by the 2026-10-18 design review.

## Done
- Frontend: the five star buttons in `LeaveReviewModal.vue` show a teal
  `focus-visible` ring. The backlog entry is removed.
- Frontend: the four `text-[15px]` paragraphs (hotel description and
  cancellation policy, booking notes, the landing page's steps) are `text-base`.
- Backend: `/dashboard` was a placeholder no page linked to (sign-in lands on
  `/bookings`). It is now `Route::redirect('/dashboard', '/bookings')`, so old
  bookmarks still work. `DashboardController`, `Dashboard.vue` and its spec are
  gone; the shared-prop tests that loaded `/dashboard` load `/profile` instead.
  The design capture drops the `16-dashboard` shot (now 23 states).
- Docs: `design-system.md` gained a row for long text, "no `text-[NNpx]`", a
  keyboard-focus rule, and the native date picker as the one date-format
  exception — browsers show it in the visitor's language, and a page cannot
  change that; chosen dates are repeated through `formatDate()`.

## Not done
- `docs/design-system.html` is not rebuilt here: it comes in with PR #85.
  Run `php docs-src/build.php` after both are merged.
- `resources/views/welcome.blade.php` (Laravel's starter page, with an inline
  copy of Tailwind) is still in the repo. No route uses it; left for a sweep.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `composer test`: 551 passed. Coverage could not be measured in the local
  container (no pcov); CI measures it.
- `vendor/bin/pint --test`: pass. `bun run test`: 342 passed.
  `bun run lint`: 0 errors, the same 5 old warnings.
- The new star test failed before the fix and passed after.
- `bun run design:capture`: 8 passed; the hotel page shows the new text size.
