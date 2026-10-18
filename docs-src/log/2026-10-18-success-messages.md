---
title: Success messages on every page
description: The customer layout now shows the server's success message after any action, over the page, so a review or a pet change no longer ends in silence.
date: 2026-10-18
pr: 72
plan: ~
---

# Success messages on every page

## Asked
The backlog entry from the persona run: seven actions send a success message,
but only the booking detail page showed one. After Daniel's review, nothing
said it worked.

## Done
- `AppLayout.vue` shows `flash.success` in a `Notice` fixed under the nav, on
  every page. It is over the page rather than at the top of the content,
  because reviews and the password form keep the scroll position and a message
  up top would be off screen. It hides after five seconds and has a Dismiss
  button; `role="status"` so screen readers read it. It watches the flash
  object, so the same message shows again after a repeated action.
- Removed the duplicates: the booking detail page's own flash `Notice` and
  Profile's two inline "Saved!" labels. Profile now says "Profile updated." or
  "Password updated." through the layout.
- Backlog entry removed; the gap is marked fixed in `personas.md`.

## Not done
- `flash.status` (auth pages under `AuthLayout`) is unchanged.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- Vitest: four new `AppLayout` tests (shown, none without a message, dismiss,
  hides after five seconds); the two booking-detail flash tests and three
  Profile "Saved!" tests removed with what they tested. `bun run test`: 300
  passed. `bun run lint`: 0 errors, the same 5 existing warnings.
- `bun run e2e`: 22 passed, 1 failed — A6 (owner daily overview, unrelated
  to this change) read the row before the save landed; the file passed twice
  on rerun. A flaky test, not fixed here.
- On the dev stack, a profile save showed "Profile updated." and Dismiss
  hid it, at desktop and phone widths (screenshots checked).
- PHPUnit: 527 passed (no PHP change).
