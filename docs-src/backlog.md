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

## Link form labels to their inputs

*Found 2026-10-11, while writing the e2e suite.* `FormField.vue` renders a
`<label>` beside the input but not tied to it (no `for`/`id`), so screen
readers do not announce the field name, clicking the label does not focus the
field, and tests cannot find fields by label (the e2e helpers fall back to
input types).

Touches: `resources/js/Components/Ui/FormField.vue` (generate an id and pass
it to the slotted input), the inputs in `Components/Ui/`, and their Vitest
specs. Then the e2e helpers can switch to `getByLabel`.

## One owner, several hotels

*Asked 2026-10-10.* The owner panel uses the first hotel an owner is attached
to (`ResolvesOwnerHotel::ownerHotel()`); any other hotel is unreachable.

Touches: a hotel switcher (Filament tenancy is the obvious fit, or a stored
"current hotel"), every owner page and resource that calls `ownerHotel()`, the
pending-count badge, and the notification links, which should open the right
hotel.
