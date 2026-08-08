---
plan: hotel-photo-urls
status: implemented
branch: feature/hotel-photo-urls
pr: 18
implemented: 2026-08-08
---

# Feature: Hotel Photo URLs

## What & Why

Hotel photos have never rendered from an upload. `HotelCard.vue` and
`HotelProfilePage.vue` bind `hotel.cover_photo` and `photo.url` straight into
`:src`, but those columns hold storage *paths* — `cover-photos/abc.jpg` — not
URLs. A bare path in `:src` resolves against the current page, so the browser
requests `/hotels/cover-photos/abc.jpg` and gets nothing.

It looks fine today only because `PetHotelSeeder` seeds absolute
`picsum.photos` URLs. Every photo an admin has ever uploaded through Filament
has been broken, and the seed data hides it.

That leaves both columns holding two different kinds of value — an absolute URL
from the seeder, a storage path from an upload — so the fix has to resolve
either.

## Scope

- `cover_photo_url` on `PetHotel` and `photo_url` on `PetHotelPhoto`, appended
  to serialization so every payload that already sends a hotel gets them with no
  controller change.
- Both resolve a storage path through `config('filesystems.photos')` and pass an
  already-absolute URL through untouched.
- `HotelCard.vue` and `HotelProfilePage.vue` read the new attributes.
- The admin `ImageColumn` reads the same resolved attribute, so the relation
  manager's thumbnails work for uploads too.

## Out of Scope

- Normalising the stored values to one format. The seeder deliberately points at
  remote demo images, and a migration that rewrote real uploads would be far
  riskier than resolving at read time.
- Renaming or dropping the `cover_photo` / `url` columns. Filament's
  `FileUpload` reads and writes those raw, so they stay exactly as they are.
- Pet photos. `PetController` already resolves those correctly.

## Technical Approach

### Backend

- `app/Support/PhotoUrl.php` — one `resolve(?string $value): ?string`. Null
  passes through; a value with a scheme, or a protocol-relative `//host/...`,
  is returned unchanged; anything else is a path resolved against the uploads
  disk.
- `app/Models/PetHotel.php` — `cover_photo_url` accessor, added to `$appends`.
- `app/Models/PetHotelPhoto.php` — `photo_url` accessor, added to `$appends`.
- `PhotosRelationManager` — `ImageColumn::make('photo_url')`.

Accessors rather than controller mapping, because `HotelController`,
`LandingController`, and `HotelSearchController` all serialise hotel models
wholesale — mapping would mean rewriting three payloads and would silently miss
the next one.

New attributes rather than overriding `cover_photo` / `url`: Filament's
`FileUpload` state is the raw column, and rewriting it on read would break
editing a hotel the moment the form round-tripped a URL back into the column.

### Frontend

- `HotelCard.vue` — `hotel.cover_photo_url`.
- `HotelProfilePage.vue` — the gallery normalises cover and gallery photos into
  one shape, so the template stops reading `.url` off two differently-shaped
  objects.

## Acceptance Criteria

- [x] An uploaded cover photo renders on the card and the profile page
- [x] A seeded absolute URL still renders unchanged
- [x] A hotel with no cover photo still shows the placeholder
- [x] Filament's upload form still round-trips the raw path
- [x] `composer test` and `bun run test` pass; Pint and ESLint clean

## Edge Cases

- **Protocol-relative URLs** (`//images.example.com/x.jpg`) have no scheme but
  are still absolute. Treated as absolute.
- **Windows-style or leading-slash paths.** A value starting `/` is already
  root-relative and must not be pushed through the disk a second time.
- **`photos` relation ordering** is `sort_order`; appending an attribute must
  not disturb it.

## Open Questions

None.
