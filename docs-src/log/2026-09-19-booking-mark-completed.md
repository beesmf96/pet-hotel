---
title: Mark completed action for bookings
description: Owners and admins can now move a confirmed booking to completed, which is the status the review flow requires and which nothing could set before.
date: 2026-09-19
pr: ~
plan: ~
---

# Mark completed action for bookings

## Asked
A readiness check before inviting friends to test found that reviews could
never be written on a live site: `ReviewController::store` only accepts a
booking with status `completed`, and no action in either panel set it. The
user chose a manual action over a scheduled job, with the rule that owners
may complete a stay only on or after its check-out date and admins at any
time.

## Done
- Filament: the owner panel's bookings table gained a "Mark completed"
  action, visible on `confirmed` bookings whose `check_out` is today or
  earlier. The admin table gained the same action on any `confirmed`
  booking. Both set the status to `completed`; no notification job fires,
  as `Booking::booted()` only reacts to `confirmed` and `cancelled`.
- Docs: `docs/user_guide.html` (hand-written, in `STATIC_DOCS`) describes
  the action in the admin bookings section and in a new owner section
  "5. Completing a Stay".

## Not done
- No automatic completion after check-out. That needs the Laravel scheduler,
  which neither Docker nor the Cloud environment runs today. The manual
  action can stay as the override if a scheduled job is added later.
- No "stay complete, leave a review" notification to the customer.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- Seven new Livewire tests: owner completes after check-out, action visible
  on the check-out day, hidden before check-out, hidden for pending and
  completed; admin completes, action visible before check-out, hidden for
  pending and completed.
- `composer test` in the app container: 390 passed, 1347 assertions.
- `vendor/bin/pint --dirty`: 4 files pass.
- No frontend change; Vitest and ESLint not rerun.
