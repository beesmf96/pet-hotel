---
title: Frontend interaction tests and a coverage floor
description: Added click and submit tests for the four weakest Vue pages, made Vitest cover every source file, and set a 60% line floor that CI enforces.
date: 2026-09-16
pr: ~
plan: ~
---

# Frontend interaction tests and a coverage floor

## Asked
A coverage check showed the backend at 98.5% lines with a CI floor of 95,
and the frontend at 81% lines with no floor. The gap was user actions:
tests mounted pages but never clicked or submitted. Add interaction tests
for the weak pages and set a floor.

## Done
- 41 new Vitest cases across five files:
  - `PetFormModal`: add posts to `/pets`, edit patches `/pets/{id}`,
    `onSuccess` emits close, Cancel and backdrop close, the pet prop fills
    the form, the file input stores the photo, every field renders its error.
  - `Pets`: the add and edit buttons open the modal with the right pet,
    close resets it, Remove deletes only when the confirm is accepted.
  - `Profile`: the profile form patches `/profile`; the password form's
    `onSuccess` and `onError` callbacks reset the right fields.
  - `SearchPage`: SearchBar and FilterSidebar events call `router.get` with
    the right params and options, sort change reloads, pagination clicks
    visit or stay put, `«`/`»` entities are decoded.
  - `HotelProfilePage`: gallery next, previous, wrap-around, and dots;
    facilities, policy, and pricing cards; the "View all" reviews link.
- Coverage config consolidated into `vitest.config.ts`. It now includes
  every `.vue` file and the composables, so files no test imports count as
  zero instead of being invisible. The dead `test` block in `vite.config.js`
  was removed, and `__dirname` replaced with `import.meta.dirname`.
- Floor: `MIN_COVERAGE = 60` on lines and statements in `vitest.config.ts`.
  CI's frontend job now runs `bun run test --run --coverage`, which fails
  below the floor.

## Not done
- 15 source files still have no test at all: the five Auth pages, both
  layouts, `Dashboard`, `BookingConfirmationPage`, `SearchBar`,
  `FilterSidebar`, `LeaveReviewModal`, `NotificationBell`,
  `NotificationsDropdown`. They are why the honest total is 61.7% lines,
  not the 91% the tested files alone reach. Each needs its own test file;
  left for a follow-up.
- The seven pre-existing ESLint warnings (missing prop defaults, one
  attribute linebreak) are in source files this work did not touch.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
`bun run test --run --coverage`: 154 tests pass, lines 61.7% (348/564),
threshold check passes. Setting the floor to 85 first was tried and failed
as expected, which proves the gate works. `bun run lint`: 0 errors,
7 pre-existing warnings. No PHP changed; `composer test` not rerun.
