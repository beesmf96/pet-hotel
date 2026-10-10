---
title: Owners are told about new and cancelled requests
description: Every owner of a hotel now gets an email and an owner-panel notification when a guest requests or cancels a stay, and the Bookings menu shows the pending count.
date: 2026-10-10
pr: ~
plan: plan-owner-notifications
---

# Owners are told about new and cancelled requests

## Asked
Item 3 of the booking-process review: only the customer heard about a new
request, so it sat pending until the owner happened to open `/owner`. The
user chose email, a bell in the owner panel and a pending count, plus an
email when a guest cancels, and asked to note SMS and WhatsApp for later.

## Done
- Backend: notifications `NewBookingRequest` and `BookingCancelledByGuest`
  (shared base `OwnerBookingNotification`) send mail and a Filament-format
  database notification: guest, pet and type, dates and nights, total in RM,
  notes, and an "Open bookings" link to the owner panel.
- Backend: jobs `NotifyOwnersOfBookingRequest` and
  `NotifyOwnersOfGuestCancellation` send to every owner of the hotel, with the
  same retry and after-commit policy as the other booking jobs. They are
  dispatched from `BookingController@store` and `@cancel`; `Booking::booted()`
  cannot tell a guest's cancel from an owner's decline.
- The new notifications do not implement `ShouldQueue`. The job is the queued
  unit, so its retry policy covers the mail send — unlike the three customer
  notifications, which are queued twice (a later review item).
- Migration: `notifications.data` becomes `json` on PostgreSQL. Filament's
  bell queries `data->format`, which PostgreSQL refuses on `text`. SQLite is
  unchanged.
- `User::customerNotifications()` drops the panel's notifications from the
  customer bell, its unread count, and both "mark read" endpoints, so an owner
  who also books sees each message in one place only.
- Owner panel: `->databaseNotifications()` (polls every 30 seconds) and a
  warning-coloured pending count on Bookings.
- Docs: user guide ("Staying Informed"), booking-flow steps 8 and 9, SRS
  FR-32a, FR-35, FR-45, and PID out-of-scope rows for SMS / WhatsApp and
  pending-request reminders.

## Not done
- SMS, WhatsApp and reminders: recorded in the PID as out of scope.
- No admin notifications.
- If the mail send fails for the second of two owners, a retry emails the
  first owner again. Rare, and a duplicate beats a missed request.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- New `OwnerNotificationTest` (16): dispatch on request and guest cancel, none
  on decline, owners of that hotel only, no owners, content, panel format,
  customer bell separation, mark-read scoping, pending badge, panel bell.
- `composer test`: 479 passed, 1662 assertions. Coverage 98.34% of lines.
  Pint clean. No JS changed.
- Against the local PostgreSQL database: migration up, down and up; a real
  owner notification found by Filament's `data->format` query and absent
  from `customerNotifications()`.
