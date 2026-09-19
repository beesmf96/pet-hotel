---
title: Pet species is a fixed pet type list
description: The pet form's species is now a select over the same five keys hotels price by, backed by a PetType enum and a migration that folds existing free text onto the list.
date: 2026-09-19
pr: ~
plan: ~
---

# Pet species is a fixed pet type list

## Asked
Item 2 of the readiness check before inviting friends to test. Pet species
was free text and the form's placeholder suggested "Dog, Cat, Rabbit", but
`BookingController` matches pricing on the exact lowercase key. A pet saved
as "Dog" found no price and the booking went through at RM 0.

## Done
- Backend: `App\Enums\PetType` (`dog`, `cat`, `rabbit`, `bird`, `other`)
  is the one PHP list. `StorePetRequest` and `UpdatePetRequest` validate
  species with `Rule::enum`, so "Dog" and "Golden Retriever" are rejected.
  The admin pricing form reads its options from the same enum.
- Backend: migration `normalise_pet_species_to_pet_types` lowercases every
  existing `pets.species` and sets anything still outside the list to
  `other`. `down()` is a no-op: the free text is gone.
- Frontend: `resources/js/petTypes.js` holds the JS copy (`PET_TYPES` and
  `petTypeLabel()`). The pet form's species field is a select; the search
  bar and the hotel profile's pricing labels read the shared list instead of
  their own copies; the pets page, booking form and booking detail show the
  label ("Dog") rather than the stored key.
- Docs: the user guide's pet fields table no longer tells customers to type
  lowercase names.

## Not done
- No database constraint on `pets.species`; validation is the guard.
- The enum and the JS list are two copies kept in step by hand, with a
  Vitest case pinning the JS keys.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- New PHPUnit tests: store rejects an unknown species and a case-variant,
  update rejects and leaves the row unchanged. Fixtures that posted "Dog"
  now post "dog".
- `composer test` in the app container: 386 passed, 1330 assertions.
  `vendor/bin/pint --dirty`: 9 files pass.
- Migration run against the local Postgres stack with two throwaway pets:
  "Dog" became `dog`, "Golden Retriever" became `other`. Both deleted after.
- New Vitest cases: the species select's options and values, the pets page
  shows the label not the key, and `petTypes.js` keys match the enum.
  `bun run test`: 36 files, 264 tests passed; coverage 94.0% lines.
  `bun run lint`: 0 errors, the same 5 warnings as `dev`.
