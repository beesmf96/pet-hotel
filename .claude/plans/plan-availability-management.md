---
plan: availability-management
status: implemented
branch: feature/availability-management
pr: 61
implemented: 2026-10-10
---

# Feature: Availability Management

## What & Why

Booking-process review, blockers 1 and 2 (2026-10-10). Availability is a
per-date "spots left" counter in `hotel_availabilities.available_spots`, and
only `HotelAvailabilitySeeder` writes those rows. No panel lets anyone set
spots or close a date. A date with no row counts as open with no limit, so a
new hotel, or a seeded hotel after its three seeded months, can be overbooked
without end. On top of that, `BookingController@store` and both panels'
Confirm actions never check spots or closed dates (SRS FR-22, OI-4).

The user chose "capacity, count bookings" over a stored counter, after a
worked example showed a counter going wrong when capacity changes. Each hotel
gets a normal capacity. For any date the owner may close it or set a
different capacity. Spots left are never stored: they are that night's
capacity minus confirmed and completed bookings covering it.

The user also asked to show check-in and check-out times wherever a stay's
dates are shown, and to let owners set their own times.

## Scope

- `pet_hotels.capacity`: the normal number of pets per night.
- `hotel_availabilities` becomes per-date overrides: `is_blocked` and a
  nullable `capacity` (null = use the hotel's capacity).
- One place computes availability: `App\Support\Availability`. The calendar
  endpoint, booking request and Confirm all use it.
- A booking request is rejected when any of its nights is closed or has no
  spots left.
- Confirm (both panels) is refused, with a notice, when the stay no longer
  fits. Confirm runs in a transaction holding a lock on the hotel row, so two
  confirms cannot both take the last spot.
- Owner panel: an "Availability" page to close dates or change capacity for
  a date range, and list, edit or delete those overrides.
- Owner panel: a "Hotel settings" page for capacity and check-in and
  check-out times.
- Admin hotel form: a capacity field.
- Check-in and check-out times on the booking form, confirmation page,
  booking detail page, and the request and confirmed emails.
- Calendar: a stay may not run across a closed or full night; check-out may
  land on a closed or full day, as it is not a night.

## Out of Scope

- Customer cancellation rules, owner notification on new requests, the
  `$` in emails, RM 0 bookings, decline wording, the double-queued
  notifications: later items of the same review, one PR each.
- Owners editing the cancellation policy, name, photos or pricing.
- Admin availability page. Admins can still use the owner panel if attached.
- Late pick-up or half-day pricing.
- Pending bookings do not hold a spot. Spots are taken on confirm, as today.

## Technical Approach

### Backend
- Migration: add `capacity` (unsigned small int, default 10) to `pet_hotels`.
  Rename `hotel_availabilities.available_spots` to `capacity`, nullable.
  Data: each existing row's new capacity is its old spots left plus the
  confirmed and completed bookings covering that night, so the spots
  customers see do not change.
- `App\Support\Availability`:
  - `nights(PetHotel, from, to)` → per night: capacity, booked, spots left,
    blocked. Booked counts `confirmed` and `completed` bookings with
    `check_in <= night < check_out`.
  - `fits(PetHotel, checkIn, checkOut)` → every night open with a spot left.
- `Booking::booted()` loses the `updating` spot adjustment; the `updated`
  notification dispatch stays.
- `Booking::confirm()`: transaction, `lockForUpdate` on the hotel row,
  `fits()` check, then status update. Throws `BookingDoesNotFit` when the
  stay no longer fits. Both panels call it and turn the exception into a
  danger notification.
- `BookingController@store`: drop the unused row lock; validation error on
  `check_in` when the stay does not fit.
- `HotelAvailabilityController`: reads from `Availability`.
- Owner panel: `AvailabilityResource` (scoped to the owner's hotel) with a
  "Set dates" header action that upserts a range; `HotelSettings` page.
- Seeders: overrides only — Sundays closed, Saturdays capacity 3.
- No `HotelAvailability` factory (tests `create([...])` it); `PetHotel` gets a
  `capacity` attribute default so in-memory hotels have one.

### Frontend
- `AvailabilityCalendar.vue`: range selection checks the nights between.
- `BookingFormPage`, `BookingConfirmationPage`, `BookingDetailPage`: show
  times when the hotel has a policy (one Vitest case per branch).
- `BookingController@create`, `confirmation`, `show`: pass the policy times.

## Follow-up (same PR, after manual testing)
- "Limited" is relative: half or fewer of the night's capacity
  (`Availability::status()`), so a small hotel is not orange every day.
- Calendar shows "X spots left" on hover.
- Owner "Daily overview" page: capacity, booked, spots left, status per night.
- Daycare recorded as out of scope in the PID.

## Acceptance Criteria
- [x] A hotel with no override rows has `capacity` spots on every night
- [x] Spots left = night capacity − confirmed/completed bookings
- [x] Changing hotel capacity changes spots left on every date at once
- [x] Request on a closed or full night → validation error, no booking
- [x] Confirm when full → refused with a notice, status stays pending
- [x] Cancel or decline frees the spot with no stored counter
- [x] Owner can close a date range and set capacity for a range
- [x] Owner can set capacity and check-in/out times for their hotel only
- [x] Times shown on form, confirmation, detail and both emails
- [x] Existing data keeps the same spots left after the migration

## Edge Cases
- Capacity lowered below confirmed bookings: spots left shows 0 (never
  negative); existing bookings stay.
- Override capacity 0 behaves as full, not closed.
- Hotel with no policy row: times are hidden; settings page creates it.
- Owner sets a range in the past: allowed, harmless.
- Range "Set dates" over existing overrides: upsert replaces them.
- "Clear overrides" for a range: delete rows so dates fall back to normal.

## Open Questions
- Default capacity for existing and new hotels: assumed 10, the old column
  default.
