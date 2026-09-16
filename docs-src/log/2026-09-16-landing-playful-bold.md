---
title: Landing page redesign (playful bold)
description: Rebuilt the landing page in the "Playful bold" direction chosen from three design proposals.
date: 2026-09-16
pr: ~
plan: ~
---

# Landing page redesign (playful bold)

## Asked
"i want to explore the landing page design, where currently might be less
attractive for a pet hotel searching platform. can we have 3 different design
proposal, see which one is good?" The user then picked direction B, "Playful
bold".

## Done
- Design: three directions (warm editorial, playful bold, clean marketplace)
  drafted as a design canvas artifact for comparison; B was chosen.
- Frontend: `Landing.vue` rebuilt in the chosen style. Chunky display type,
  cream background, thick ink outlines with hard offset shadows, teal, mustard
  and coral accents. Emoji icons replaced with inline SVG. Decorative shapes
  hide below the `lg` breakpoint. The featured section hides when there are no
  hotels.
- Frontend: new `FeaturedHotelCard.vue` for the landing grid so the shared
  `HotelCard.vue` on the search page is untouched. Placeholder tint cycles by
  hotel id; unrated hotels show "New".
- Frontend: `SearchBar.vue` gained a `variant="bold"` prop that drops its own
  box and restyles fields and button. The default look is unchanged.
- Styling: `app.css` now imports the self-hosted Bricolage Grotesque variable
  font and defines `--font-display`, the landing palette and two hard-shadow
  tokens in `@theme`.
- Owner call to action links to `/register` for guests and `/owner` for signed
  in users. There is no dedicated owner onboarding page yet.

## Not done
- The search page and hotel profile keep their old look. Rolling the new
  palette across the rest of the app is a separate task.
- The design canvas still shows all three directions; it was not trimmed to
  the chosen one.

## Produced
- ADR: none
- Knowledge: none
- Intake: ../intake/fontsource-bricolage-grotesque.md

## Verified
- `bun run test`: 29 files, 237 tests passed (new specs for the featured card,
  the bold search bar, and both featured-section branches).
- `bun run lint`: 0 errors, 8 warnings, same count as `dev` (the existing
  `require-default-prop` pattern).
- `composer test` in Docker: 377 passed. `vendor/bin/pint --test`: pass.
- Headless Chromium screenshots at 1440px and 400px wide: no horizontal
  overflow, headline on two lines on desktop, search fields stack on phone.
