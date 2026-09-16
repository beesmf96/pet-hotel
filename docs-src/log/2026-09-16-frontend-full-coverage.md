---
title: Tests for the 15 untested Vue files, floor raised to 90
description: Every Vue file and composable now has a test file; honest line coverage went from 61.7% to 92.6% and the CI floor from 60 to 90.
date: 2026-09-16
pr: 45
plan: ~
---

# Tests for the 15 untested Vue files, floor raised to 90

## Asked
Follow-up to PR #43, which listed 15 source files with no test at all and
set the frontend coverage floor at 60 against that honest measurement. Add
tests for those files and raise the floor.

## Done
- 68 new Vitest cases in 14 new files under `resources/js/tests/`, mirroring
  the source tree:
  - Layouts: `AppLayout` (guest vs signed-in nav, bell count, Sign out
    posts to `/logout`, header and nav slots), `AuthLayout` (slots, brand link).
  - Pages: `Dashboard`; `Auth/Login` (status banner, submit clears the
    password, remember checkbox, plain-anchor Google link), `Auth/Register`,
    `Auth/ResetPassword` (token and email seeded from props),
    `Auth/ForgotPassword`, `Auth/VerifyEmail` (resend vs sign-out forms);
    `Bookings/BookingConfirmationPage` (formatted dates, two-decimal total).
  - Components: `SearchBar` (seeding, emitted payload, Enter key),
    `FilterSidebar` (array and comma-string facilities, apply and clear
    payloads), `LeaveReviewModal` (star picker, hover preview, disabled
    submit, post and close), `NotificationBell` (badge cap at 99+, toggle,
    outside click, listener cleanup), `NotificationsDropdown` (fetch with
    JSON headers, empty and failed states, type icons, mark-read PATCH with
    the CSRF token, navigate only when a url exists, mark-all-read).
- `MIN_COVERAGE` in `vitest.config.ts` raised from 60 to 90.

## Not done
- Seven files still read under 90% lines in the v8 report: `Register`
  (60%), `ResetPassword` (70%), `Profile` (73%), `PetFormModal` (83%),
  `BookingFormPage` (83%), `Landing` (86%), `AvailabilityCalendar` (87%).
  For the form pages the "uncovered" lines are `<input v-model>` blocks;
  v8 counts the generated setters, which never run when `useForm` is
  mocked with a plain object. Covering them would mean mounting with the
  real Inertia form, which needs an Inertia page context. Left as is.
- No branch or function threshold. Lines and statements only, as before.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
`bun run test --run --coverage`: 222 tests pass; lines 92.55% (522/564),
statements 92.25%, branches 94.05%, functions 84.26%; the 90 threshold
check passes. `bun run lint`: 0 errors, the same 7 pre-existing warnings.
No PHP changed; `composer test` not rerun.
