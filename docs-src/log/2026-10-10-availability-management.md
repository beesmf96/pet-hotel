---
title: Capacity-based availability, owner date controls, and stay times
description: Spots left are now computed from a hotel capacity minus confirmed bookings; requests and confirms are checked; owners close dates, change capacity and set check-in and check-out times; times show on every booking page and email.
date: 2026-10-10
pr: ~
plan: plan-availability-management
---

# Capacity-based availability, owner date controls, and stay times

## Asked
A review of the booking process found that nobody could manage availability
(only the seeder wrote `hotel_availabilities`), that a date with no row had no
limit, and that neither a request nor Confirm checked spots or closed dates
(SRS OI-4). Of three designs walked through with a worked example, the user
chose "capacity, count bookings": a stored counter went wrong as soon as the
hotel's capacity changed. The user also asked to show check-in and check-out
times with the dates, and to let owners set them.

## Done
- Data: `pet_hotels.capacity` (default 10). `hotel_availabilities.available_spots`
  became a nullable `capacity` override; `is_blocked` stays. The migration
  adds back the confirmed and completed bookings to each existing row, so the
  spots customers see are unchanged. `down()` reverses it.
- Backend: `App\Support\Availability` is the one place that works out spots
  left (night capacity minus confirmed and completed bookings) and whether a
  stay fits. The check-out day is not a night.
- Backend: `Booking::booted()` no longer adjusts spots; it only dispatches
  notifications. `Booking::confirm()` locks the hotel row, checks the stay
  still fits, and throws `BookingDoesNotFit` if not. Both panels' Confirm
  buttons use it and show "Cannot confirm this booking".
- Backend: `BookingController@store` rejects a stay over a closed or full night
  with a `check_in` error. Pending requests still hold no spot.
- Backend: the three booking jobs are `ShouldQueueAfterCommit`, because the
  confirmed job is now dispatched inside `confirm()`'s transaction.
- Owner panel: an **Availability** page ("Change dates" and "Reset dates" over
  a range, plus edit and reset per date) and a **Hotel settings** page
  (capacity and check-in and check-out times). A `ResolvesOwnerHotel` trait
  replaces the bookings resource's private lookup.
- Admin panel: a capacity field on the hotel form.
- Frontend: the calendar will not let a stay cross a full or closed night, but
  lets check-out land on one. The booking form, confirmation and detail pages
  show the times. The form prints the server's date error instead of always
  saying "Please select a check-in date."
- Email: request and confirmed emails add "from 2:00 PM" and "by 12:00 PM".
- Seeder: overrides only — Sundays closed, Saturdays capacity 3.
- Docs: user guide (customer booking notes, admin capacity, owner sections 6
  and 7), SRS v1.2 (FR-22 done, FR-23 rewritten, FR-23a, FR-46, FR-47, DR-03,
  NFR-17, OI-4 closed), PID risk R3, CLAUDE.md trap and testing notes.
  CLAUDE.md also said `MIN_COVERAGE` was 95; CI has 98, so it now says 98.

## Not done
- The other review items: owner not told about new requests, `$` in emails,
  RM 0 bookings, decline email wording, double-queued notifications.
- No admin availability page; admins attached to a hotel can use the owner one.
- Owners cannot edit the cancellation policy, which still lives on the admin
  form only.
- The calendar checks only the month on screen; a stay across months is
  checked by the server when submitted.
- Local databases keep one override row per seeded date after the migration.
  `php artisan migrate:fresh --seed` gives the lighter seed.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `composer test`: 451 passed, 1578 assertions. New `AvailabilityTest` (15),
  owner `AvailabilityResourceTest` (9) and `HotelSettingsTest` (6), plus
  booking, calendar, email and panel confirm cases.
- Coverage with pcov: 98.35% of lines; every new class at 100%.
- `vendor/bin/pint`: clean. `bun run test`: 275 passed. `bun run lint`: 0
  errors, the same 5 existing warnings.
- Migration up, down and up again on the local PostgreSQL database.
- `php docs-src/build.php` re-rendered `pid.html` and `srs.html`.
