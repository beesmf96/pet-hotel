---
title: Stakeholder docs audit
description: Checked the user guide, booking flow, SRS, PID, and deployment checklist against the code; fixed the stale parts, retired the task list, made MYR the currency everywhere, loaded the average rating for search cards, and made the Docker cache actually use Redis.
date: 2026-09-16
pr: 49
plan: ~
---

# Stakeholder docs audit

## Asked
"audit our user guide html see if it is still as accurate as per our source",
then "update the guide and fix the USD label too". A follow-up asked for
the same audit of `docs/booking-flow.html`, with "MYR wins" for the
currency and a fix for the search-card rating. A third follow-up asked for the
same audit of the SRS and PID, with "redis should be true" for the cache. A
fourth asked for the deployment checklist, and to remove the task list from the
index and, if possible, altogether "since it is already done". A fifth asked to
"fix the dev env photo disk too" after the live CSP showed no bucket origin.

## Done
- Docs: `docs/user_guide.html` (a hand-written page in `STATIC_DOCS`, not
  rendered from markdown) was compared against routes, controllers, form
  requests, Vue pages, and both Filament panels. Wrong claims fixed: panel
  URLs are `/admin` and `/owner` (the subdomains were dropped long ago), no
  Distance sort in the UI, pet species is free text, "Leave a review" sits on
  the My Bookings list and opens a modal with an optional comment, calendar
  legend is Available / Limited / Full / Unavailable, the widget is named
  "Check-ins Today", owners register on the customer site before an admin
  attaches them, and any attached account (including an admin) can enter the
  owner panel. Gaps filled: Google sign-in, change or set password on the
  profile page, search-bar dates, hotel map, the "request received"
  notification and email delivery, the `/dashboard` landing page, and the
  admin View actions and filters.
- Docs: `docs/booking-flow.html` fixed the same way. Sort is by newest or
  price (no rating or distance), pet type is picked in the search bar, the
  button is "Request Booking", the hotel owner is not emailed on a new
  request, panel paths are `/owner` and `/admin`, the decline email carries
  no reason. Added Google sign-in, self-cancel while pending, the calendar
  spot decrement on confirm, and the no-pricing warning on the form.
- Docs: `docs-src/srs.md` (v1.1). Stay dates no longer claimed as a filter
  (new OI-6), sort is newest or price with distance noted as backend-only,
  customer cancel is pending-only and OI-1 rewritten (cancellation policy is
  free text, not enforced), review comment optional, coverage floors 98 and
  90, CSP wording admits the OSM tile hosts, NFR-21 no longer links to a
  page that is never rendered. Added FR-07a (password change or set),
  FR-18a (landing page), map in FR-16, View pages in FR-39 and FR-40.
- Docs: `docs-src/pid.md` (v1.1). O1 sorting claim, coverage floors,
  branching model (feature to dev, release PR to main), CI triggers include
  dev, stack versions at major level only, Redis for queue, cache, and
  sessions, password change in scope, fuller post-MVP list, audit gates in
  the quality-gate list. Change-control row added.
- Docs: `docs/srs.html` and `docs/pid.html` regenerated with
  `php docs-src/build.php`. Before the edits the build reproduced the
  committed HTML exactly, so the HTML had not drifted from the markdown.
- Docs: `docs-src/deployment-checklist.md` checked claim by claim against
  `composer.json`, `config/`, the env template, and live headers from the
  `dev` environment. Nearly all of it holds: the S3 and AWS SDK packages are
  still absent, the Google redirect default, the session secure flag, the
  CSP mode, the mail default, the cookie name, HSTS, and the known gaps are
  all as described. Changed: the `dev` URL does not send `X-Robots-Tag`, so
  that sentence is now a check rather than a promise; PHP 8.4 to match
  Docker; the two `composer require` steps now say to write the intake
  entry first.
- Docs: `docs-src/tasks.md` and `docs/tasks.html` deleted. Every checkbox
  was ticked and the paper trail (ADR-0001) already made the log the record
  of what shipped. References in `CLAUDE.md`, the SRS, and the PID now point
  to the log; the PID milestones table stays as the summary. ADR-0001 keeps
  its historical mention, as ADRs are not edited after acceptance. The file
  remains in git history.
- Infra: added `league/flysystem-aws-s3-v3` (^3.0, 3.35.3 installed; pulls
  in `aws/aws-sdk-php` 3.395.3, `aws/aws-crt-php`, `mtdowling/jmespath.php`,
  `symfony/filesystem`). The `s3` disk was configured and `PHOTO_DISK=s3`
  was documented, but the adapter was never installed, so the `dev`
  environment could not have switched. Intake entry written first. Smoke
  test: with placeholder credentials `Storage::disk('s3')` boots the
  `AwsS3V3Adapter` and builds `AWS_URL`-based URLs. `composer audit` clean.
  Checklist step 1 is now ticked and step 6 no longer asks for the SDK.
- Backend: the Cloud docs say R2 rejects writes with a public ACL. The
  `s3` disk set `'visibility' => 'public'` and `PetController` used
  `storePublicly()`, so the first upload on Cloud would have failed with
  `NotImplemented`. Visibility removed from the disk, uploads use `store()`,
  and the disk now also reads Cloud's `AWS_REGION` and `AWS_ENDPOINT_URL`
  names as fallbacks. Two tests pin the rules. Knowledge entry written.
- Docs: checklist step 3 now explains the **Disk name** field (3 to 40
  lowercase characters, so `s3` is refused; use `photos`, default disk off)
  and the `FILESYSTEM_DISK` override if Cloud injects the bucket's name.
  CLAUDE.md upload trap extended.
- Infra: `.env.docker` set `CACHE_DRIVER=redis`, a key Laravel no longer
  reads, so the Docker cache silently fell back to the database store. Now
  `CACHE_STORE=redis`. A running stack needs the same change in its `.env`.
- Backend: the admin bookings table formatted `total_price` as USD while the
  owner panel and pricing tab use MYR. Changed to MYR.
- Frontend: every customer page showed prices with a dollar sign (hotel
  card, hotel profile, booking form, confirmation, detail, My Bookings).
  All now show `RM`.
- Backend: `HotelSearchController` never loaded `reviews_avg_rating`, so
  search cards always showed a dash for rating. It now uses `withAvg`, the
  same as the landing page. Hidden reviews are excluded because the
  relation already scopes to visible ones.
- Tests: one Filament test asserts the admin table renders `MYR 150.00`
  (the formatter emits a non-breaking space); one search test asserts the
  average rating is returned and ignores hidden reviews; three Vitest
  assertions updated from `$` to `RM`.

## Not done
- The Cloud dashboard half of the photo-disk fix cannot be done from the
  repository: attach a Laravel Object Storage bucket to `dev`, set
  `PHOTO_DISK=s3` and `AWS_URL`, redeploy, then upload a photo and confirm
  the CSP `img-src` lists the bucket. Steps 3 and 4 of the checklist.
- No screenshots were retaken; the guide has none.
- The guide still says nothing about admin bulk delete on bookings and
  photos beyond what was already there; those were judged fine as-is.

## Produced
- ADR: none
- Knowledge: ../knowledge/r2-rejects-public-acl.md
- Intake: ../intake/flysystem-aws-s3-v3.md

## Verified
- `composer test` in the app container: 375 passed, 1309 assertions (rerun
  after the S3 adapter install, same result).
- `composer audit`: no advisories.
- `vendor/bin/pint --dirty`: clean.
- `bun run test`: 222 passed across 28 files.
- `bun run lint`: 0 errors, 7 warnings, all present on `dev` before this
  branch (missing prop defaults and one attribute linebreak).
