---
title: One place a night per pet
description: A pet can no longer be requested or confirmed at two hotels for the same night.
date: 2026-10-11
pr: 65
plan: ~
---

# One place a night per pet

## Asked
Minor item from the booking-process review: the same pet could be booked at
two hotels for the same nights. The user chose to fix it first.

## Done
- `Booking::clashFor()` finds the pet's booking that shares a night with a
  stay. Back-to-back stays do not clash: the check-out day is not a night.
- Request: rejected when the pet has a pending, confirmed or completed
  booking on a shared night, at any hotel. The message names the hotel and
  dates, and says to cancel the request first when the other one is pending.
  Checked after the price and availability checks.
- Confirm: refused when the pet is confirmed or completed elsewhere on a
  shared night. `confirm()` now locks the pet row after the hotel row, so two
  hotels cannot confirm the same pet at once. Other pending requests are
  ignored there.
- `BookingDoesNotFit` has two named reasons (`noRoom()`, `petAlreadyBooked()`),
  each with its own advice; both panels show the message as it is. The owner
  message does not name the other hotel.
- SRS: new FR-22a. E2e: new step C4b (a second request for Rex is refused).

## Not done
- Pending requests still never expire — the other open review item.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- New tests: a request clashing with a pending request elsewhere and with a
  confirmed stay (exact messages), back-to-back stays allowed, cancelled
  bookings ignored, confirm refused for a pet confirmed elsewhere, confirm
  ignoring other pending requests, and the owner-panel notice.
- PHPUnit: 502 passed, 1736 assertions. Coverage 98.44% of lines. Pint clean.
  `bun run test`: 285 passed. `bun run lint`: 0 errors, the same 5 existing
  warnings. `bun run e2e`: 22 passed.
