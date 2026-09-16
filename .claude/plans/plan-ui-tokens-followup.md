---
plan: ui-tokens-followup
status: implemented
branch: feature/ui-tokens-followup
pr: ~
implemented: 2026-09-16
---

# Feature: UI tokens follow-up

## What & Why
The playful bold redesign (PR #51) left two maintenance gaps. The design
tokens exist twice: as Tailwind `@theme` values in `resources/css/app.css`
and again as `--ph-*` variables in `resources/css/filament/theme.css`, because
the Filament theme is a separate Tailwind build. And nothing in CLAUDE.md
tells future work to use the shared Ui components, so pages could drift back
to inline class bundles. This plan closes both.

## Scope
- One shared token file, for example `resources/css/tokens.css`, holding the
  seven colours (ink, cream, teal, teal-light, coral, mustard, moss), the
  display font stack, and the two hard shadows as plain CSS custom properties.
- `app.css` imports it and maps the properties into `@theme` so the Tailwind
  utilities (`bg-teal`, `shadow-hard`, `font-display`) keep working unchanged.
- `filament/theme.css` imports it and drops its own `--ph-*` block, using the
  shared names in its rules.
- A short rule in CLAUDE.md under "Traps and decisions": new customer UI uses
  the components under `resources/js/Components/Ui/` and the `card-hard` /
  `field-hard` utilities; no inline class bundles for buttons, fields, cards,
  pills or notices. Panel styling goes in the Filament theme, never in a
  resource.
- A session log entry.

## Out of Scope
- Any visual change. Both builds must render pixel-identical before and after.
- Visual regression tooling in CI (a separate decision).
- Touching the four deliberate inline islands: landing hero and step tiles,
  calendar day cells, hotel profile policy tiles.

## Technical Approach

### Backend
- None.

### Frontend
- Add `resources/css/tokens.css`. Import it first in `app.css`; in `@theme`
  reference the properties (`--color-ink: var(--ph-ink)` or move the values
  outright and reference them from the Filament theme). Check that Tailwind
  v4 resolves the `@theme` values at build time; if `var()` inside `@theme`
  does not produce working utilities, keep literal values in `tokens.css`
  under `@theme` and `@import` that file into both entries instead.
- In `filament/theme.css`, replace the `:root { --ph-* }` block with the
  import and rename usages if the shared names differ.
- `bun run build`, then screenshot landing, search, login, admin dashboard
  and owner bookings and compare with the ones from 2026-09-16 (same pages,
  same widths) to confirm nothing moved.

### Docs
- CLAUDE.md rule as above, three or four lines.
- Log entry in `docs-src/log/`.

## Acceptance Criteria
- [x] Each colour, the display font and the shadows are defined in exactly one
      file, and `grep` for the hex values finds them only there.
- [x] `bun run build` succeeds and both stylesheets are emitted.
- [x] Vitest and PHPUnit pass unchanged.
- [x] Screenshots of the five pages match the previous session's.
- [x] CLAUDE.md carries the Ui-components rule.

## Edge Cases
- Tailwind v4 `@theme` may not accept `var()` for colour utilities that need
  opacity modifiers (`bg-ink/60`). If so, keep literal values in the shared
  file under `@theme` and import it into both builds.
- The Filament theme is compiled with `source(none)`; make sure the shared
  file does not carry `@source` or `@import 'tailwindcss'` a second time.

## Open Questions
- None. Assumption: literal values in a shared `@theme` file imported by both
  entries is the simplest path and is tried first.
