---
title: league/flysystem-aws-s3-v3
description: Flysystem adapter that lets the `s3` disk talk to S3-compatible object storage, which Laravel Cloud needs for photos.
date: 2026-09-16
status: accepted
kind: composer
scope: runtime
source: https://github.com/thephpleague/flysystem
license: MIT
version: ^3.0 (3.35.3 installed)
---

# league/flysystem-aws-s3-v3

## Why we need it
Laravel Cloud compute is ephemeral: every deploy wipes the container
filesystem, so pet and hotel photos have to go to the attached object
storage bucket. `config/filesystems.php` already defines the `s3` disk and
`PHOTO_DISK=s3` already selects it, but Laravel's S3 driver needs this
adapter to run and it was never installed. The first upload on `dev` with
`PHOTO_DISK=s3` would have thrown. Step 1 of the deployment checklist has
listed this as a prerequisite since the checklist was written.

Without it: keep `PHOTO_DISK=public` on Cloud and lose every photo on every
deploy. Not acceptable for a hosted environment.

The package is a read-only subtree split of the Flysystem monorepo; issues
and releases live in `thephpleague/flysystem`.

## Maintenance (checked 2026-09-16)
- Last release: 3.35.3, 2026-08-08. Last push to the monorepo: 2026-09-02.
- Release cadence: six tagged releases in the twelve months to August 2026,
  each following an AWS SDK or Flysystem core bump.
- Maintainers: one person (Frank de Jonge) wrote 42 of 59 commits in the
  last year. A handful of drive-by contributors. Bus factor is one, but the
  package is the de facto S3 adapter for Laravel and Laravel itself lists it
  as the suggested S3 dependency.
- Issues: 103 open across the whole monorepo, 13.6k stars, not archived.

## Security (checked 2026-09-16)
- Known CVEs or advisories: none for the adapter itself. The core
  `league/flysystem` has one historical advisory, GHSA-9f46-5r25-5wfm
  (TOCTOU race), fixed in 1.1.4, far below the 3.x line Laravel 13 already
  ships. `aws/aws-sdk-php`, the adapter's main dependency, has four past
  advisories, the latest GHSA-27qh-8cxx-2cr5 (CloudFront policy injection)
  fixed in 3.371.4; the adapter's own constraint is `^3.371.5`, so the fixed
  line is the minimum.
- Time to fix past advisories: the SDK's were fixed in the release the
  advisory named, so effectively at disclosure.
- Transitive dependencies: `aws/aws-sdk-php` (Apache-2.0) is the big one,
  with its own tree (Guzzle, PSR interfaces, mtdowling/jmespath.php). Guzzle
  and PSR are already in the project through Laravel. Nothing flagged.
- Where checked: osv.dev API, GitHub Advisory Database, Packagist.

## Alternatives
- Keep photos on the local disk: lost on every Cloud deploy. That is the
  problem, not an option.
- A different object-storage adapter (Google Cloud, Azure): Cloud's bucket
  is S3-compatible, and the `s3` disk is already configured.
- Signed URLs through a controller: avoids nothing, the adapter is still
  needed to reach the bucket.

## Verdict
Accepted. Runtime scope, MIT, no advisories on the adapter, the only
practical way to use Laravel Cloud's object storage, and the checklist has
required it all along. Note the SDK's advisory history: keep `composer
audit` in CI green and bump the SDK when it flags.
