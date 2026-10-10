---
title: Booking review fixes — currency, RM 0 bookings, cancel wording, double queuing
description: Prices print through one currency setting and are summed in sen; a pet type with no price cannot be booked; the cancellation email says who cancelled; customer notifications are queued once; deferred requests go in a new backlog.
date: 2026-10-10
pr: 63
plan: ~
---

# Booking review fixes — currency, RM 0 bookings, cancel wording, double queuing

## Asked
Items 4 to 7 of the booking-process review, in one PR. For currency the user
asked what is best long term for a Malaysia-first product; the answer taken
was MYR only, set in one place, summed in sen, with per-hotel currency left
for a second market. The user also asked to record three deferred features.

## Done
- #4 Currency: `config('app.currency')` (MYR, "RM"), `App\Support\Money`
  (`format`, `toSen`, `fromSen`) and its JS twin `resources/js/money.js`
  (`formatMoney`, `toSen`). Every price now goes through them: the two
  customer emails that printed `$`, the owner email, both panels' booking
  tables and the pricing tab (Filament's `money('MYR')` printed "MYR 150.00"),
  the hotel card and profile, and the four booking pages. Prices gain
  thousands separators ("RM 1,300.00"). Totals are summed in sen on the server
  and in the booking form.
- #5 RM 0 bookings: a request for a pet type the hotel has no price for is
  rejected with a message naming the pet and type, and the form disables
  Request Booking and says why. Closes SRS OI-5.
- #6 Cancel wording: `Booking::booted()` is gone. `Booking::confirm()` and the
  new `Booking::cancel(CancelledBy)` dispatch the emails, so the guest's email
  can say "You cancelled your booking request", "… could not take your
  booking request" (decline; "No booking was made", "Find Another Stay"), or
  "… has cancelled your confirmed booking". Only the guest's own cancel
  carries "If you did not cancel it yourself, contact support". The owners'
  cancellation notice moved into `cancel()` too.
- #7 Double queuing: `BookingRequested`, `BookingConfirmed` and
  `BookingCancelled` no longer implement `ShouldQueue`; the job is the one
  queued unit, so its retries cover the mail send.
- Docs: new coder-facing `docs-src/backlog.md` with the three deferred
  features (booking pop-up from a notification, owners editing hotel details,
  one owner with several hotels). CLAUDE.md: status-change and money rules,
  backlog pointer. SRS FR-21, FR-32, FR-33, OI-5. PID currency row. User
  guide decline/cancel emails and the no-price rule.

## Not done
- No decline reason field; the decline email still gives no reason.
- `Mark completed` still uses a plain status update, on purpose: it sends
  nothing.
- Per-hotel currency, exchange rates: out of scope until a second market.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- New `MoneyTest`, `money.test.js`; booking tests for the no-price rejection
  and an exact sen total (3 × RM 0.10 = "0.30"); wording tests for the three
  cancellation emails; dispatch tests for `confirm()`, both `cancel()` paths
  and a plain update; panel decline and cancel send the hotel wording; the
  policy test now covers all five notification jobs.
- `composer test`: 491 passed, 1707 assertions. Coverage 98.42% of lines.
  Pint clean. `bun run test`: 285 passed. `bun run lint`: 0 errors, the same
  5 existing warnings.
