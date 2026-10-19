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

## Calendar view of availability for owners

*Asked 2026-10-18, after testing the booking pop-up.* The owner panel's Daily
Overview shows capacity, booked and spots left as a table, one row per day.
Owners would see full and closed nights faster on a month grid.

Touches: the Daily Overview page (a list/calendar toggle, or a new page), a
Blade month grid inside the owner panel with styling in
`resources/css/filament/theme.css` (no calendar package; a package needs an
intake entry), and `App\Support\Availability` for the numbers. Each day shows
spots left out of capacity, its state (free, nearly full, full, closed) and
the pending-request count; clicking a day lists that night's bookings, each
opening the booking pop-up. Decide whether the first version is read-only or
also blocks days and changes capacity, which would overlap the Availability
list.
