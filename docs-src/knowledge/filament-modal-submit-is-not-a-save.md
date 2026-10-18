---
title: A Filament modal closing is not a save, and a click during a live re-render can be lost
description: E2e steps that submit a Filament action and move on must wait for the save request itself, or the save is cancelled or never sent.
date: 2026-10-18
tags: e2e, playwright, filament, livewire, flaky
---

# A Filament modal closing is not a save, and a click during a live re-render can be lost

## What we learnt
Filament hides an action modal as soon as Submit is clicked, before the
server has answered. The save is a Livewire request whose body calls
`callMountedAction`; a `page.goto()` while it is out cancels it. Separately,
a `->live()` field (a toggle, a date) sends its own request when it changes,
and a click on Submit while that request is out can be lost in the
re-render: no `callMountedAction` is sent at all, with no error.

## How we found out
E2e test A6 failed now and then: a night it had just closed showed as open.
Logging the Livewire requests showed the helper returning while the save
was still out, and the next `page.goto()` cutting it off. Waiting for the
modal to close did not help (it closes at once). Waiting for the save then
timed out once in 25 runs: the Submit click had landed during the "Closed"
toggle's own request and sent nothing. Slowing only the save request by
1.5 s made the old helper fail every time.

## How to apply
In e2e helpers that drive a Filament form: after changing a live field,
wait for its Livewire response before the next click; after Submit, wait
for the response whose request body contains `"callMountedAction"`. Wait
for the form to be visible before the first live field, so the response
awaited is the field's own and not the modal's. See `changeDates()` in
`e2e/support/owner.js` and [the log](../log/2026-10-18-flaky-a6.md).
