---
title: Cloudflare R2 rejects uploads that carry a public ACL
description: Laravel Cloud Object Storage is R2, so an S3 disk with visibility 'public' or a storePublicly() call fails on the first upload.
date: 2026-09-16
tags: laravel-cloud, storage, s3, r2, uploads
---

# Cloudflare R2 rejects uploads that carry a public ACL

## What we learnt
Laravel Cloud Object Storage is Cloudflare R2 behind an S3 API. R2 manages
visibility per bucket and has no per-object ACLs, so a `PutObject` carrying
`x-amz-acl: public-read` is answered with `NotImplemented`. Flysystem's S3
adapter adds that header whenever the write's visibility is `public`, which
happens when the disk config sets `'visibility' => 'public'` or the code
calls `storePublicly()`. A `private` ACL is accepted, and a public bucket
still serves the object by plain URL.

Filament's `FileUpload::visibility('public')` is safe: it stores without an
ACL and only calls `setVisibility()` afterwards inside a `rescue()`, so the
failed ACL call is swallowed.

## How we found out
The deployment checklist audit on 2026-09-16 read the Cloud object storage
docs, which warn against `visibility: 'public'` in the disk config. Our
`s3` disk had exactly that, and `PetController` used `storePublicly()`.
Neither had ever run against a real bucket because the S3 adapter package
was missing until the same day.

## How to apply
Leave `visibility` out of the `s3` disk. Use `store()`, never
`storePublicly()`, and let the bucket's visibility decide. Make the Cloud
bucket public so `Storage::url()` links work. `tests/Feature/UploadDiskTest.php`
pins both rules. Log: `../log/2026-09-16-user-guide-audit.md`.
