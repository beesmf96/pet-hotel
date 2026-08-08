---
plan: object-storage
status: implemented
branch: feature/object-storage
pr: 16
implemented: 2026-08-08
---

# Feature: Object Storage for Uploads

## What & Why

Every uploaded image currently lands on the container's local filesystem.
Laravel Cloud runs the app in ephemeral containers, so each deploy wipes
`storage/app/public`, and with more than one container an upload is only visible
to whichever container happened to serve the request. Pet photos and hotel
photos silently disappear.

The disk each upload uses is also inconsistent today, which has to be settled
before pointing anything at S3:

- `PetController` writes with `->store('pet-photos', 'public')` — the **public**
  disk — but reads URLs back with `Storage::url()`, which resolves against the
  **default** disk (`FILESYSTEM_DISK=local`). The two happen to produce the same
  `/storage/...` string today, so the `public/storage` symlink covers the
  mismatch. Point one of them at S3 and it stops working.
- The two Filament `FileUpload` fields (`HotelResource` cover photo,
  `PhotosRelationManager`) declare no disk at all, so they inherit the default
  disk rather than the one `PetController` writes to.

## Scope

- A single configured disk for all user uploads: `filesystems.photos`,
  `PHOTO_DISK`, defaulting to `public` so local development is unchanged.
- `PetController` writes, deletes, and builds URLs through that one disk.
- Both Filament `FileUpload` fields pinned to it, with public visibility.
- Uploads written with public visibility so S3 objects are readable.
- `SecurityHeaders` CSP `img-src` widened to the storage origin when the
  configured disk serves from a different host — otherwise every uploaded image
  is blocked the moment `CSP_MODE` goes to `enforce`.
- `.env.example` documents `PHOTO_DISK` and the `AWS_*` values Cloud needs.

## Out of Scope

- **Hotel photos do not render from uploads.** `HotelCard.vue` and
  `HotelProfilePage.vue` bind `hotel.cover_photo` and `photo.url` straight into
  `:src`, but those columns hold storage *paths*. It only looks right today
  because `PetHotelSeeder` seeds absolute picsum.photos URLs. Uploading a photo
  through Filament has always produced a broken image. Pre-existing, unrelated
  to which disk is used, and fixing it means reshaping the hotel payload plus
  its Vue tests — a separate PR.
- Migrating any existing uploaded files to the bucket. There is no production
  upload worth keeping yet.
- The queue worker, SMTP, and production env vars — separate changes.

## Technical Approach

### Backend

- `config/filesystems.php` — add `'photos' => env('PHOTO_DISK', 'public')`, and
  `'visibility' => 'public'` on the `s3` disk.
- `app/Http/Controllers/PetController.php` — resolve the disk once from config;
  use `storePublicly()` so the object is world-readable on S3, and
  `Storage::disk($disk)->url()` so the URL comes from the same disk that holds
  the file.
- `app/Filament/Resources/HotelResource.php` and
  `.../RelationManagers/PhotosRelationManager.php` — `->disk(...)->visibility('public')`.
- `app/Http/Middleware/SecurityHeaders.php` — add the configured disk's origin to
  `img-src` when it is absolute and not the app's own origin. Read from config
  rather than calling `Storage`, so a misconfigured disk cannot throw from
  middleware on every request.

### Frontend

None. `Pets.vue` already renders whatever `photo_url` the controller sends.

## Acceptance Criteria

- [x] Uploads and URL generation use the same disk, whatever `PHOTO_DISK` is
- [x] `PHOTO_DISK=s3` sends pet photos to the bucket and returns bucket URLs
- [x] Default (`public`) behaviour is unchanged for local development
- [x] Filament uploads land on the same disk as controller uploads
- [x] CSP `img-src` covers an off-origin bucket
- [x] CSP is unchanged when the disk is same-origin
- [x] `composer test` passes, `vendor/bin/pint` clean

## Edge Cases

- **`AWS_URL` unset.** Then `filesystems.disks.s3.url` is null and the origin
  cannot be derived for the CSP. Falls back to `AWS_ENDPOINT`; if both are unset
  the policy is left alone rather than guessing a bucket URL. Documented in
  `.env.example`.
- **Relative disk URL.** The `public` disk's URL is built from `APP_URL`, so it
  is same-origin and must add nothing to the policy.
- **Deleting an old photo on replace** must target the configured disk, or
  replacing a photo silently leaks the old object.

## Open Questions

None.
