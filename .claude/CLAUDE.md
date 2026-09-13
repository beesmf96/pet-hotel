# Pet Hotel — Stack, Domain & Conventions

Customers search, book, and review pet boarding stays; hotel owners manage listings through a Filament panel.

This file records what you cannot infer from reading the code: decisions, traps, and the
handful of places where the codebase deliberately deviates from framework defaults. Match
everything else — naming, structure, style — to the surrounding code.

## Stack

Laravel 13 · Vue 3 + Vite + Tailwind v4 (no `tailwind.config.js`) · Inertia 3 · Filament 4
(two panels) · Sanctum cookie SPA + Google OAuth via Socialite · PostgreSQL in Docker, SQLite
locally and in every test · Redis queue in Docker (`.env.example` ships `database`) · Leaflet
for maps · Bun (never npm/pnpm) · Pint + ESLint/Prettier · PHPUnit 12 · Vitest 4.

Exact versions: `composer.json` / `package.json`. No component library, no TypeScript,
no Pinia/Vuex, no `routes/api.php`, no Blade views outside the Filament panels.

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

**Two Filament panels**, both routed by path on the app's own domain — `/admin` requires `is_admin`, `/owner` requires `ownedHotels()->exists()`. Do not reintroduce `->domain()` on a panel without also updating `SecurityHeaders::isFilamentRequest()`, which decides where Filament's `'unsafe-eval'` CSP relaxation applies. Resources auto-discover from `app/Filament/Resources/` and `app/Filament/HotelOwner/Resources/` respectively. Colour tokens: amber (admin), teal (owner). Owner-panel resources must scope `getEloquentQuery()` to `ownedHotels()` — never show a hotel owner every record.

**All uploads go through `config('filesystems.photos')`** — pet photos in `PetController`, and both Filament `FileUpload` fields. Never name a disk literally at an upload site, and never build an upload's URL from a different disk than the one it was written to. `PHOTO_DISK` selects it; it must be `s3` on ephemeral hosting or uploads are lost on deploy. `SecurityHeaders` reads the same config to allow the bucket in the CSP `img-src`.

**Customer-facing routes return Inertia, redirects, or `back()` — never `response()->json()`.** Two JSON endpoints exist and are intentional; both are XHR-backed widgets, not page loads:
- `hotels.availability` → `HotelAvailabilityController@index`, feeds `AvailabilityCalendar.vue`
- `notifications.*` → `NotificationController` (`index`, `markRead`, `markAllRead`)

Any *other* customer-facing JSON response is a violation.

**OAuth.** `users.google_id` is nullable-unique and `users.password` is null for OAuth-created accounts — that null is the *only* signal that an account has no password the user could type, and `PasswordController` relies on it to skip the `current_password` check. Never detect an OAuth account by `google_id !== null` instead: a user who registered with a password and later linked Google has both, and must still confirm their current password. Laravel's hasher rejects a null hash, so such accounts simply cannot log in by password until one is set. Never write a random-string password for an OAuth account. Config in `config/services.php` → `google`; requires `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`.

**`password` is outside `$fillable`.** Assign it with `forceFill([...])->save()` (see `PasswordController`, `ResetPasswordController`) — `update()` silently drops it. `User::forceCreate()` is likewise reserved for registration and first-party OAuth creation, where non-fillable attributes (`password`, `google_id`, `email_verified_at`) are written on purpose.

**Guest auth routes carry `throttle:5,1`** — login, register, password reset, OAuth redirect and callback. Match it on any new guest-facing auth endpoint.

**The OAuth entry link is a plain `<a href="/auth/google">`**, not an Inertia `<Link>` — `<Link>` issues an XHR and will not follow the 302 to Google. Everything else navigates with `<Link>` / `router.visit()`, never `window.location`.

**Inertia forms:** `useForm()` for anything with a body or validation errors. Body-less actions (`logout`, `cancel`, `destroy`) use `router.post/patch/delete` directly — both are established in the codebase.

**Every page wraps itself in a layout** — `<AppLayout>` or `<AuthLayout>` as the template root, or `defineOptions({ layout })`. `Landing.vue` is the one `layout: null` page because it renders its own shell.

## Testing

Feature tests are the main coverage; `tests/Feature/BookingTest.php` is the canonical shape.

- Factories only, never seeders. Not every model has one (`HotelAvailability` doesn't — `create([...])` it).
- `User::factory()->admin()` and `->hotelOwner($hotel)` states exist.
- `Queue::fake()` / `Mail::fake()` whenever the code under test dispatches or sends, and assert the fake received the expected class.
- Filament resources are tested with `Livewire::test(ListBookings::class)` etc. under `tests/Feature/Filament/` — not via HTTP.
- Booking flow tests assert `available_spots` decrements on confirm and re-increments on cancel.
- Socialite is mocked at the facade (`Socialite::shouldReceive('driver')`); `GoogleAuthTest.php` is the pattern.
- Vitest specs live in `resources/js/tests/{Components,Pages}/` mirroring `resources/js/`. When a page gains an Inertia-prop-driven `v-if` branch, add one test per branch — PHP tests only prove the props arrive.

## Not yet built

- No payment integration
- No API layer (`routes/api.php` does not exist)
- Hotel-owner Filament panel has only `BookingResource`
- Google is the only Socialite provider configured
