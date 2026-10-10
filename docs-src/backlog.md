---
title: Backlog
description: Features the user has asked for and deferred, with what each one involves. Not a commitment to order or dates.
badges: Planning
order: 60
---

# Backlog

Features agreed as wanted but deferred. Each entry says what was asked and
what it touches, so whoever picks it up starts from the same understanding.
When one is picked up, it gets a plan in `.claude/plans/` and is removed from
here in the same PR. Things decided *against* for now live in the PID's
out-of-scope table instead (SMS/WhatsApp, pending reminders, daycare, payments).

## Booking pop-up from an owner notification

*Asked 2026-10-10.* Clicking a notification in the owner panel's bell opens
the Bookings list today. Instead it should open a short summary of that one
booking — guest, pet and its details (species, breed, age, notes), dates and
nights, total, the guest's notes — with **Confirm** and **Decline** on it.

Touches: the `OwnerBookingNotification` action URL (it needs the booking id),
a Filament view action or modal on the owner `BookingResource`, and the same
`Booking::confirm()` / `cancel()` paths the row actions use.

## Owners edit their hotel details

*Asked 2026-10-10.* Owners can set capacity, closed dates and check-in/out
times, but name, description, address, photos, pricing, facilities and the
cancellation policy are admin-only.

Touches: owner-panel forms for those fields (the admin `HotelResource` and its
relation managers are the model), photo uploads through
`config('filesystems.photos')`, and the PID's "Full hotel-owner self-service"
out-of-scope row, which this would retire. Decide whether the slug and the
`is_active` switch stay admin-only.

## One owner, several hotels

*Asked 2026-10-10.* The owner panel uses the first hotel an owner is attached
to (`ResolvesOwnerHotel::ownerHotel()`); any other hotel is unreachable.

Touches: a hotel switcher (Filament tenancy is the obvious fit, or a stored
"current hotel"), every owner page and resource that calls `ownerHotel()`, the
pending-count badge, and the notification links, which should open the right
hotel.
