---
title: An answer deadline for requests
description: A hotel must answer a request within 48 hours, or by noon the day before check-in if sooner; the customer and the owner see the deadline, and unanswered requests expire at it.
date: 2026-10-18
pr: 75
plan: ~
---

# An answer deadline for requests

## Asked
Backlog item from the persona run: a request could stay pending until the
morning after its check-in day, so a customer could learn after the trip had
started that the pet had no place. The user chose: 48 hours; or noon the day
before check-in if that is sooner; owners see the deadline on the list rows
and in the pop-up.

## Done
- `Booking::answerBy()`: 48 hours after the request, or noon the day before
  check-in if sooner. A request sent after that noon (the decision did not
  cover it) has until the end of its check-in day, which was the old rule.
  `answerByText()` formats it.
- New `config('app.display_timezone')` (`Asia/Kuala_Lumpur`): the app runs
  in UTC, but "noon" and the times people read are Malaysian. The owner
  pop-up's "Requested" time now uses it too; it was 8 hours off.
- `bookings:expire-pending` cancels pending requests at or past their
  deadline, and now runs every five minutes instead of daily at 00:15 UTC.
  The expiry email says the hotel "did not answer your booking request in
  time".
- Customer: the form note gives the rule; the confirmation page's "What
  happens next" box and the booking detail page show the deadline; the
  request email names it.
- Owner: an **Answer by** column for pending rows (short form,
  "20 Oct, 9:41 PM"), an **Answer by** entry in the pop-up, and "Please
  answer by …, or the request expires." in the new-request email and bell.
- Docs: SRS FR-26a rewritten, with an "Answer deadline" definition and a
  table of three worked examples; user guide (customer and owner), with a
  "When the Hotel Must Answer" section and the same examples; deployment
  checklist's scheduler step; personas gap marked fixed, and a note that the
  seeded pending request expires 48 hours after seeding; backlog entry
  removed.

## Not done
- No reminder to owners before the deadline (the PID's out-of-scope row on
  pending reminders stands).
- `Booking::confirm()` does not check the deadline: an owner who confirms in
  the few minutes before the next expiry run still confirms, and the guest
  never gets the expiry email, which is consistent.
- On deploy, any pending request already older than its deadline expires on
  the first run.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- New `BookingAnswerByTest` (4): 48 hours; noon the day before; after that
  noon, end of check-in day (day before and same day); local formatting.
- `ExpirePendingBookingsTest`: past the deadline expires (49 h), inside it
  does not (47 h), a near request expires at noon the day before and not a
  minute earlier; runs `*/5 * * * *`. Owner panel: the column and pop-up show
  the deadline for pending and nothing once answered; "Requested" in local
  time. Page props on the confirmation and detail pages; both emails.
- Vitest: deadline and no-deadline branches on the confirmation and detail
  pages; the form note. `bun run test`: 306 passed. `bun run lint`: 0
  errors, the same 5 existing warnings.
- PHPUnit: 536 passed. Pint clean. `bun run e2e`: 23 passed.
- The three documented examples, run through `answerByText()` in tinker,
  give the times the docs state.
- Dev stack: a request sent at 9:41 PM showed "Tue, 20 Oct 2026, 9:41 PM" on
  the confirmation page, in Rahman's email, in the list (short form) and in
  the pop-up (screenshots checked). `schedule:list` shows `*/5 * * * *`.
