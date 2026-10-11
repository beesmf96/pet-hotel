---
title: Pending requests expire
description: A daily scheduled command cancels booking requests still pending after their check-in day and emails the customer; the app gets its first scheduler.
date: 2026-10-11
pr: ~
plan: ~
---

# Pending requests expire

## Asked
The last open item from the booking-process review: a request the hotel never
answers stays pending forever. The user asked to start it after the pet
double-booking fix.

## Done
- `bookings:expire-pending` cancels every pending request whose check-in day
  has passed, through `Booking::cancel(CancelledBy::Expiry)`. Scheduled daily
  at 00:15 UTC (8:15 AM in Malaysia), so a request expires the morning after
  its check-in day — the owner can still answer on the day itself.
- New `CancelledBy::Expiry`. The customer's email: "Booking Request Expired",
  "… did not answer your booking request before check-in, so it has
  expired.", "No booking was made.", "Find Another Stay". Owners get nothing;
  their pending count simply drops.
- Scheduler: new `scheduler` Docker service (`schedule:work`, same image as
  `queue`), `composer dev` runs it too, deployment checklist step 6 now says
  to turn on Laravel Cloud's scheduler, CLAUDE.md notes it beside the worker.
- SRS: new FR-26a; FR-32 lists the fourth email wording.

## Not done
- Reminders to owners for requests left pending (PID out-of-scope row stays).
- A separate `expired` status: the booking reads "cancelled" in lists. The
  email says why.

## Produced
- ADR: none
- Knowledge: none
- Intake: none (the scheduler service reuses the existing app image)

## Verified
- New `ExpirePendingBookingsTest`: past check-in expires and dispatches the
  expiry email only; today and later stay pending; confirmed bookings are left
  alone; the schedule is `15 0 * * *`. New expiry wording test in
  `NotificationTest`.
- `schedule:list` in Docker shows the task; `pet_hotel_scheduler` is up.
- PHPUnit: 507 passed, 1755 assertions. Coverage 98.45% of lines. Pint clean.
  No frontend change; Vitest, ESLint and e2e not affected.
