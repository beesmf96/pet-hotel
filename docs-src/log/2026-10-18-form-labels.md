---
title: Form labels tied to their inputs
description: Every customer form label now points at its field, so clicking it focuses the field and screen readers announce the name and any error; the e2e helpers find fields by label.
date: 2026-10-18
pr: ~
plan: ~
---

# Form labels tied to their inputs

## Asked
Backlog item from the e2e work: `FormField.vue` rendered a `<label>` with no
`for`, so screen readers did not announce field names, clicking a label did
nothing, and tests had to find fields by input type.

## Done
- `FormField` makes an id with Vue's `useId()`, puts it on the label's `for`,
  and provides it (with the error's id, when there is an error) through
  `formFieldKey` (`Components/Ui/formField.js`). It also passes `id` and
  `labelId` as slot props for controls that are not a `TextInput`.
- `TextInput` takes the id by itself, and `aria-describedby` /
  `aria-invalid` when the field has an error. An id passed in directly still
  wins. So the nine pages and components that use them needed no change.
- The two other controls: the pet **Photo** file input takes the `id` slot
  prop; the review **star rating** is a `role="group"` labelled "Rating",
  each star a button announced as "3 stars" with `aria-pressed` on the
  chosen one (`group`, not `radiogroup`, which would promise arrow keys).
- e2e: `loginCustomer()`, `fillRequest()` and the B and F specs find fields
  with `getByLabel` instead of `input[type=…]` / the first `select`. The
  owner-panel login still finds the password by type; Filament's labels
  were already tied.
- SRS: new NFR-07a. Backlog entry removed.

## Not done
- The star buttons still have `focus:outline-none`, so keyboard focus on
  them is invisible. Noted, not changed.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- Vitest: new tests for the label's `for` matching the field id, unique ids
  per field, the error tie (and none without an error), the slot props, a
  TextInput outside a FormField, an explicit id winning, the Photo label,
  and the star group's label, names and pressed state. Run against the old
  components they fail; two that first passed by comparing `undefined` with
  `undefined` were tightened. `bun run test`: 314 passed. `bun run lint`:
  0 errors, the same 5 existing warnings.
- `bun run e2e`: 23 passed, now finding fields by label.
- Dev stack: clicking the "Email" label on the login page focuses the field;
  after a failed sign-in the accessibility tree reads `textbox "Email"
  [invalid]`, described by the error message.
- No PHP change, so PHPUnit was not re-run.
