---
plan: owner-booking-popup
status: implemented
branch: feature/owner-booking-popup
pr: 69
implemented: 2026-10-11
---

# Feature: Booking Pop-up for Owners

## What & Why
Backlog item (asked 2026-10-10). An owner's bell notification and email open
the Bookings list, so the owner has to find the row. Instead they should
open a short summary of that one booking, with Confirm and Decline on it.

## Scope
- A **View** pop-up on the owner `BookingResource`: guest (name, email,
  phone), pet (name, type, breed, age, special needs), stay (dates with the
  hotel's times, nights, total), the guest's notes, status and when it was
  requested.
- **Confirm** and **Decline** in the pop-up footer while the booking is
  pending, through the same `Booking::confirm()` / `cancel()` paths and
  messages as the row actions. The pop-up closes after either.
- Clicking a row opens the pop-up.
- Notification links (bell and email) open the list with that booking's
  pop-up already open: `?tableAction=view&tableActionRecord={id}`. Labels
  become "View booking".
- Remove the item from `docs-src/backlog.md`.

## Out of Scope
- The admin panel's bookings (unchanged).
- Notifications already stored keep their old link to the list.

## Technical Approach

### Backend
- `BookingResource::infolist()` and a `ViewAction` with
  `extraModalFooterActions`; the confirm and decline actions come from one
  factory each so the row and the pop-up stay identical.
- `->recordAction('view')` on the table.
- `OwnerBookingNotification::bookingUrl()` builds the deep link. Filament
  resolves `tableActionRecord` through the table query, which is scoped to
  the owner's hotel, so another hotel's id opens nothing.

### Frontend
- None (Filament).

## Acceptance Criteria
- [x] The pop-up shows guest, pet, stay, total and notes
- [x] Confirm and Decline work from the pop-up, with the same notices
- [x] They are hidden when the booking is not pending
- [x] The bell and email links open that booking's pop-up
- [x] Another hotel's booking id does not open

## Edge Cases
- Booking with no notes, a pet with no breed/age: entries show a dash.
- Hotel without a policy: dates shown without times.
- Booking deleted after the notification: the list opens with no pop-up.
