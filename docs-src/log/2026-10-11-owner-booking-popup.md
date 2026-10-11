---
title: Booking pop-up for owners
description: Owners open a booking's details in a pop-up — from its row, the bell or the email — and confirm or decline it there.
date: 2026-10-11
pr: ~
plan: .claude/plans/plan-owner-booking-popup.md
---

# Booking pop-up for owners

## Asked
Backlog item from 2026-10-10: clicking an owner notification should open a
short summary of that booking, with the pet's details, and Confirm and
Decline on it, instead of the Bookings list.

## Done
- Owner `BookingResource`: a **View** pop-up (infolist) with status; stay
  (dates with the hotel's check-in/out times, nights, total, guest's notes);
  pet (name, type, breed, age, special needs); guest (name, email, phone,
  when requested). Clicking a row opens it too.
- **Confirm** and **Decline** in the pop-up's footer while pending. They come
  from the same factories as the row actions (same confirmation, same
  `Booking::confirm()` / `cancel()` and refusal notices) and close the pop-up.
- Bell and email links now open `/owner/bookings?tableAction=view&tableActionRecord={id}`,
  labelled "View booking". Filament resolves the record through the table
  query, scoped to the owner's hotel, so another hotel's id opens nothing.
- The view action authorizes on the hotel instead of `BookingPolicy::view()`,
  which is the guest's own booking page and stays unchanged.
- Docs: SRS FR-45a, user guide (booking details, email and bell), backlog
  entry removed.

## Not done
- Admin panel bookings: unchanged.
- Notifications already stored keep their old link to the list.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- Panel tests: the pop-up shows stay with times, nights, total, notes, pet
  and guest details; without times or optional details; confirm and decline
  from the pop-up (status, emails, pop-up closed); no confirm or decline once
  answered; the deep link mounts the pop-up for that booking and not for
  another hotel's. Mail and bell links tested in `OwnerNotificationTest`.
- E2e C5 now confirms Alice from the bell: pop-up shows breed, notes and the
  check-in time, then Confirm.
- PHPUnit: 519 passed, 1887 assertions. Coverage 98.54% of lines. Pint clean.
  `bun run lint`: 0 errors, the same 5 existing warnings. `bun run e2e`: 22
  passed. No Vue changes, so Vitest was not re-run.
