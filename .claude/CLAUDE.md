# Pet Hotel — Stack & Domain Reference

Customers search, book, and review pet boarding stays; hotel owners manage listings through a Filament panel.

Code conventions live in `.claude/agents/coder.md`, `tester.md`, and `linter.md` — this file does not repeat them.

## Stack

| Layer | Detail |
|-------|--------|
| PHP framework | Laravel 13.8 |
| Frontend | Vue 3 + Vite 8 + Tailwind CSS v4 (no `tailwind.config.js`) |
| SPA bridge | Inertia.js 3.1 |
| Admin UI | Filament v4 — two panels |
| Auth | Sanctum cookie SPA + Google OAuth (`laravel/socialite`) |
| Database | PostgreSQL 16 (Docker) / SQLite (local, and all tests) |
| Queue | Redis (`QUEUE_CONNECTION=redis` in `.env` and `.env.docker`) |
| Maps | Leaflet — no other mapping library |
| Package manager | Bun — never npm or pnpm |
| Formatters | Laravel Pint (PHP, framework defaults) · ESLint + Prettier (JS/Vue) |
| Test runners | PHPUnit 12 · Vitest 4 |

No component library, no TypeScript, no Pinia/Vuex, no `routes/api.php`.

## Domain Model

```
users ──< pets
users ──< bookings ──> pets
users >─< pet_hotels  (pivot: hotel_owner, has role column)
pet_hotels ──< pet_hotel_facilities
pet_hotels ──< pet_hotel_photos
pet_hotels ──1 pet_hotel_policies
pet_hotels ──< pet_hotel_pricing      (per pet_type)
pet_hotels ──< hotel_availabilities   (one row per date, available_spots INT)
pet_hotels ──< bookings
bookings   ──1 reviews
users      ──< notifications          (Laravel DB notifications)
```

Hard deletes with cascading FKs throughout — soft deletes are not used.

## Non-obvious behaviour

**Availability side-effects live in `Booking::booted()` only** — spots adjust on `updating`, notification jobs fire on `updated`. Never replicate this in a controller, service, or job.

**Two Filament panels.** Admin at `admin.pet-hotel.local` requires `is_admin`; hotel owner at `owner.pet-hotel.local` requires `ownedHotels()->exists()`. Resources auto-discover from `app/Filament/Resources/` and `app/Filament/HotelOwner/Resources/` respectively. Colour tokens: amber (admin), teal (owner).

**All uploads go through `config('filesystems.photos')`** — pet photos in `PetController`, and both Filament `FileUpload` fields. Never name a disk literally at an upload site, and never build an upload's URL from a different disk than the one it was written to. `PHOTO_DISK` selects it; it must be `s3` on ephemeral hosting or uploads are lost on deploy. `SecurityHeaders` reads the same config to allow the bucket in the CSP `img-src`.

**Two JSON endpoints exist and are intentional**, despite the general "Inertia only, never `response()->json()`" rule. Both are XHR-backed widgets, not page loads:
- `hotels.availability` → `HotelAvailabilityController@index`, feeds `AvailabilityCalendar.vue`
- `notifications.*` → `NotificationController` (`index`, `markRead`, `markAllRead`)

Any *other* customer-facing JSON response is a violation.

**OAuth.** `users.google_id` is nullable-unique and `users.password` is nullable, so OAuth-only accounts have no password — never assume one is set. Config in `config/services.php` → `google`; requires `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.

## Not yet built

- No payment integration
- No API layer (`routes/api.php` does not exist)
- Hotel-owner Filament panel has only `BookingResource`
- Google is the only Socialite provider configured
