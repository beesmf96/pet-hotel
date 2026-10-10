---
plan: owner-notifications
status: implemented
branch: feature/owner-notifications
pr: 62
implemented: 2026-10-10
---

# Feature: Owner Notifications

## What & Why

Booking-process review, blocker 3 (2026-10-10). When a customer sends a
request, only the customer is told. The hotel owner gets no email, no notice
in the owner panel, and no count of waiting requests, so requests sit pending
until the owner happens to look. Pending requests hold no spot, so a slow
answer also risks the spot going to someone else.

## Scope

- Email to every owner attached to the hotel when a request arrives, with the
  guest, pet, dates, nights, total and notes, and a button to the owner panel.
- Email to the same owners when a customer cancels their pending request.
- Both also appear in a bell in the owner panel (Filament database
  notifications).
- A pending-request count on the owner panel's Bookings menu item.
- Customer notification list, unread count and "mark all read" ignore the
  owner-panel notifications, as one user can be both customer and owner.
- PID: SMS / WhatsApp notifications and pending-request reminders recorded as
  out of scope (user asked to note them for the future).

## Out of Scope

- SMS, WhatsApp, push notifications.
- Reminders for requests left pending (needs the scheduler).
- Admin notifications.
- Other review items: `$` in customer emails, RM 0 bookings, decline email
  wording, double-queued customer notifications.

## Technical Approach

### Backend
- Migration: `notifications.data` becomes `json` on PostgreSQL. Filament finds
  its notifications with `where('data->format', 'filament')`, which fails on a
  `text` column there. SQLite reads JSON from text and is left alone.
- Notifications `NewBookingRequest` and `BookingCancelledByGuest`: `mail` and
  `database`; the database payload is Filament's format. They do not
  implement `ShouldQueue` — the job is the queued unit, so its retry policy
  covers the send.
- Jobs `NotifyOwnersOfBookingRequest` and `NotifyOwnersOfGuestCancellation`,
  same retry policy as the existing booking jobs, after commit. Dispatched
  from `BookingController@store` and `@cancel`, as the request job already is.
  `Booking::booted()` cannot tell who cancelled, so it is not the place.
- `User::customerNotifications()`: notifications without the Filament format.
  Used by `NotificationController` and the shared unread count.
- Owner panel: `->databaseNotifications()`; `BookingResource` navigation
  badge with the pending count.

### Frontend
- None. The owner panel is Filament.

## Acceptance Criteria
- [x] A request emails and bell-notifies every owner of that hotel, no one else
- [x] A customer cancelling a pending request emails and bell-notifies them
- [x] An owner or admin declining or cancelling does not notify the owners
- [x] Bookings menu shows the pending count for the owner's hotel only
- [x] Customer bell and unread count never include owner notifications
- [x] Works on PostgreSQL (migration up and down)

## Edge Cases
- Hotel with no owners: the job does nothing.
- Booking deleted before the job runs: discarded, as for the other jobs.
- Owner who also books as a customer: sees each notification in the right place.
