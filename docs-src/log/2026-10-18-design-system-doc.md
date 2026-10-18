---
title: Design system doc
description: The customer UI's design rules, written down from the current code, with seven open choices settled by the user.
date: 2026-10-18
pr: 81
plan: ~
---

# Design system doc

## Asked
Make design consistency checkable: draft a written design system for the app
from the current code ("option 1", files in the repo), as the reference for a
later design-review skill.

## Done
- `docs-src/design-system.md`: direction, colour roles, shape (borders,
  corners, shadows), type scale, content widths and spacing, which component
  to use when, page patterns (header, list, detail, form, modal, auth),
  states and wording. Drawn from what most pages do today.
- Seven places where pages disagreed. The user took every proposal:
  - sentence case for all headings and buttons
  - every submit button shows a busy label, with "…"
  - `white` is the only secondary button
  - `leading-tight` is set once, in the AppLayout header
  - `<Link>` / `as="link"` for links to other pages
  - teal feature cards use `rounded-2xl`
- Corners: `lg` is allowed for small elements, because the calendar, the
  checkboxes and the bell already use it.
- CLAUDE.md points at the doc from the design-tokens rule.

## Not done
- No page was changed. Section 9 of the doc lists what does not match yet;
  the first design review will fix it.
- The design-review skill itself is not built yet.
- The Filament panels are out of scope.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
Docs only. Each rule was checked against the current Vue pages and `Ui` components.
