---
title: Fix the Attach button on hotel owners
description: The Attach pop-up on a hotel's Owners tab crashed because Filament guessed the wrong relation on User.
date: 2026-10-31
pr: 90
plan: ~
---

# Fix the Attach button on hotel owners

## Asked
"attach button no work": `/admin`, a hotel's Owners tab, Attach showed "Error
while loading page".

## Done
- Backend: `OwnersRelationManager` now names the inverse relation
  (`$inverseRelationship = 'ownedHotels'`). Filament guesses `petHotels` from the
  class name `PetHotel`; `User` has no such method, so the pop-up threw
  `BadMethodCallException`. The bug was in the code from the start and is not
  from the 2026-10-31 updates; no test had opened the pop-up.
- Tests: a test that attaches an owner through the relation manager.

## Not done
- Inviting owners by email. The owner still has to sign up first.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- The new test failed before the fix with that exception and passes after.
- The Attach pop-up opened against local Postgres through a Livewire test.
- `composer test`: 552 passed. `vendor/bin/pint --test`: pass.
