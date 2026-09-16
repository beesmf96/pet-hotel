---
title: Customer pages redesign (playful bold)
description: Rebuilt the landing page, app layout, search, hotel profile and booking form in the "Playful bold" direction chosen from three design proposals.
date: 2026-09-16
pr: 51
plan: ~
---

# Customer pages redesign (playful bold)

## Asked
"i want to explore the landing page design, where currently might be less
attractive for a pet hotel searching platform. can we have 3 different design
proposal, see which one is good?" The user then picked direction B, "Playful
bold". A second request in the same session: apply the same style to the
search page, with the shared app layout included, on the same branch. A third
request: the hotel profile page too, same branch. A fourth: the booking form.

## Done
- Design: three directions (warm editorial, playful bold, clean marketplace)
  drafted as a design canvas artifact for comparison; B was chosen.
- Frontend: `Landing.vue` rebuilt in the chosen style. Chunky display type,
  cream background, thick ink outlines with hard offset shadows, teal, mustard
  and coral accents. Emoji icons replaced with inline SVG. Decorative shapes
  hide below the `lg` breakpoint. The featured section hides when there are no
  hotels.
- Frontend: `HotelCard.vue` rebuilt in the new style and shared by the landing
  grid and the search results. Placeholder tint cycles by hotel id, unrated
  hotels show "New", price shows whole ringgit, long names clamp to two lines.
- Frontend: `AppLayout.vue` moved to the new palette. Cream ground, ink
  bottom border on the nav, paw logo, bold Register button. The header strip is
  now a display-font title on cream instead of a white bar. The pets and
  profile links hide below the `sm` breakpoint so the nav fits a phone.
- Frontend: `SearchPage.vue` uses the bold search bar, restyled sort select,
  empty state with an SVG paw, and outlined pagination buttons. The sidebar
  stacks above the results on phones.
- Frontend: `FilterSidebar.vue` restyled to match. Price labels now say RM.
- Frontend: notification bell badge is coral with an ink border.
- Frontend: `HotelProfilePage.vue` restyled. Outlined cards with hard
  shadows, display-font headings, a taller gallery with outlined arrow buttons
  and an SVG paw placeholder, facility chips without emoji, check-in and
  check-out times as tiles, and Book Now merged into the pricing card.
- Frontend: `ReviewList.vue` stars are mustard on ink; `AvailabilityCalendar.vue`
  got the new frame, month buttons and legend. Its day-cell colours are
  unchanged because the booking form shares the component.
- Frontend: `BookingFormPage.vue` restyled. Outlined cards, bold inputs,
  coral error text, and the price summary is now a teal card with the total
  in display type and both action buttons inside it. Buttons stack on phones.
- Frontend: `AvailabilityCalendar.vue` selected check-in and check-out cells
  are now ink on cream, and the nights in between are mustard. The
  available, limited, full and past cell colours are unchanged.
- Bug fix: `HotelController` and `ReviewController` sent `reviews_count` and
  `average_rating`, but both pages declare `reviewsCount` and `averageRating`.
  Vue does not map snake_case to camelCase, so the profile always showed
  "No reviews yet" and never the "View all" link, and the reviews page never
  showed its summary. The controllers now send camelCase and the feature
  tests in `ReviewTest.php` assert the new names.
- Bug fix found on the way: with no query string the search page's `filters`
  prop is an empty array, so `filters.sort` was the Array method and the sort
  select rendered blank. The page now only accepts a string value.
- Frontend: `SearchBar.vue` gained a `variant="bold"` prop that drops its own
  box and restyles fields and button. The default look is unchanged.
- Styling: `app.css` now imports the self-hosted Bricolage Grotesque variable
  font and defines `--font-display`, the landing palette and two hard-shadow
  tokens in `@theme`.
- Owner call to action links to `/register` for guests and `/owner` for signed
  in users. There is no dedicated owner onboarding page yet.

## Not done
- The booking confirmation, booking detail, my bookings, reviews list, pets,
  profile, dashboard and auth pages keep their grey cards inside the new
  chrome. Restyling them is a follow-up; the booking confirmation and my
  bookings pages are the natural next ones since the booking form leads there.
- The design canvas still shows all three directions; it was not trimmed to
  the chosen one.

## Produced
- ADR: none
- Knowledge: none
- Intake: ../intake/fontsource-bricolage-grotesque.md

## Verified
- `bun run test`: 28 files, 229 tests passed (new specs for the card, the
  bold search bar, both featured-section branches, and the empty-array sort
  default).
- `bun run lint`: 0 errors, 7 warnings, one fewer than `dev` (the existing
  `require-default-prop` pattern).
- `composer test` in Docker: 377 passed, including the renamed review prop
  assertions. `vendor/bin/pint --test`: pass.
- Headless Chromium screenshots of the landing, search, hotel profile and
  booking form pages at 1440px and 400px wide, plus the search empty state
  (the booking form was captured through the DevTools protocol with a
  throwaway logged-in session, deleted afterwards): no horizontal overflow, headline
  on two lines on desktop, fields and sidebar stack on phone, sort select
  shows "Newest first", the profile's review summary shows the average and
  count.
