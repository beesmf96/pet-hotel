---
title: Say plainly that a booking is a request
description: "Book Now" became "Request to Book", and the form, confirmation page and email now say the hotel must confirm; also fixes the booking-form calendar's month buttons, which sent the request.
date: 2026-10-18
pr: 74
plan: ~
---

# Say plainly that a booking is a request

## Asked
Backlog item from the persona run: "Book Now" sends a request the owner must
accept, nothing said so before submitting, and the confirmation page
promised the hotel "will confirm shortly".

## Done
- Hotel page: **Book Now** → **Request to Book**.
- Booking form: a note above the submit button: "This sends a request. The
  hotel confirms or declines it, and we email you either way. Your pet has a
  place only once the hotel confirms."
- Confirmation page: "pending review … will confirm shortly" → "pending. The
  hotel will confirm or decline it, and we'll email you either way.", plus a
  **What happens next** box naming the pet, and saying an unanswered request
  expires after check-in (true today; the answer-deadline item will change it).
- Request email: "confirm shortly" → the same plain line, with the pet's name.
- SRS FR-24 rewritten; user guide; personas gap marked fixed; backlog entry
  removed, and the answer-deadline entry now says to name the deadline in
  the new note and box.
- **Bug found while checking:** the calendar's month buttons (‹ ›) had no
  `type="button"`, and on the booking form the calendar sits inside the
  `<form>`. Changing month submitted the form: with no dates it showed
  "Please select a check-in date"; with a pet and dates chosen it **sent the
  request** without the customer pressing Request Booking (reproduced on the
  dev stack: booking 10 was created by clicking ›). Both buttons now have
  `type="button"`. No other untyped button sits inside a form.

## Not done
- No deadline is named yet; that is the next backlog item.
- The test request created while reproducing the bug (Mei Ling, Whiskers &
  Wags, 20–22 Nov) is left in the dev database.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- Vitest: new tests for the form note, the "What happens next" box, and a
  calendar inside a form not submitting it when the month changes (fails
  without the fix: two submits). `bun run test`: 303 passed. `bun run lint`:
  0 errors, the same 5 existing warnings.
- PHPUnit: the request email's closing line asserted. 527 passed. Pint clean.
- `bun run e2e`: 23 passed (B6 now clicks "Request to Book").
- Dev stack: screenshots of the hotel page, form and confirmation page
  checked; the email in Mailpit has the new line; after the fix, clicking ›
  with dates chosen stays on the form.
