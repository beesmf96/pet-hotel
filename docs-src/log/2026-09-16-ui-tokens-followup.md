---
title: One token file for both stylesheets
description: The playful bold palette, display font and hard shadows now live once in resources/css/tokens.css, shared by app.css and the Filament theme, with a CLAUDE.md rule pointing new UI at the shared Ui components.
date: 2026-09-16
pr: ~
plan: ../../.claude/plans/plan-ui-tokens-followup.md
---

# One token file for both stylesheets

## Asked
The follow-up agreed at the end of the redesign session (PR #51): the
design tokens existed twice, as Tailwind `@theme` values in `app.css` and
again as `--ph-*` variables in the Filament theme, and nothing in CLAUDE.md
told future work to use the shared Ui components. Close both, with no
visual change.

## Done
- Styling: new `resources/css/tokens.css` holds the seven colours, the
  display font stack and three hard shadows (`sm`, default, `lg`) in one
  `@theme static` block, and imports the Bricolage Grotesque font face.
  `static` makes Tailwind emit every property whether or not a utility
  uses it, which the Filament theme needs because its rules read the
  properties directly. The file carries no `@import 'tailwindcss'` or
  `@source`; each entry stylesheet brings its own.
- Styling: `app.css` imports the token file and keeps only `--font-sans`
  in its own `@theme`. The utilities (`bg-teal`, `font-display`,
  `shadow-hard`) are unchanged.
- Styling: `filament/theme.css` imports the token file, drops its `:root`
  block, and uses the shared names (`--color-ink`, `--font-display`,
  `--shadow-hard-sm`). The login card's `8px` shadow now reads
  `--shadow-hard-lg`. The `2px` shadow on small buttons has no token and
  stays literal.
- Filament: the brand partial's inline styles read the token variables
  instead of repeating the hex values and font stack.
- Docs: CLAUDE.md gained a trap entry: tokens live in `tokens.css` only,
  new customer UI uses the `Ui/` components and the `card-hard` /
  `field-hard` utilities, panel styling goes in the Filament theme.

## Not done
- `HotelOwnerPanelProvider` still passes teal as `Color::hex('#0f766e')`.
  That is a PHP value for Filament's own palette generator, so it cannot
  read a CSS file. It is the one place a palette hex remains outside
  `tokens.css`.
- No new tests. The change is CSS only and has no runtime behaviour to
  assert; the built CSS diff and screenshots below are the proof.
- No visual regression tooling in CI, as the plan set out.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- Built CSS, before and after, split into declarations and diffed. `app.css`
  gains one variable (`--shadow-hard-sm`). The Filament theme differs only
  in variable names, plus one alias Filament emits (`--color-primary-400`)
  that no rule uses, and the `@supports` fallbacks for the two `color-mix`
  rules now resolve to literal colours because Tailwind can see the token
  values at build time; modern browsers take the `color-mix` branch either
  way.
- Headless Chromium screenshots of landing (1440 and 400), search, login,
  register (400), admin login and owner login (400), taken before and
  after against the local Docker stack, compared byte for byte: all
  identical. The owner login shot flickered by 14 pixels at the viewport
  edge, and three reshoots of the same build produced both variants, so
  that is capture noise, not a style change.
- `composer test` in the app container: 383 passed, 1323 assertions.
  `vendor/bin/pint --test`: 161 files pass.
- `bun run test`: 35 files, 259 tests passed. `bun run lint`: 0 errors,
  the same 5 warnings as `dev`.
