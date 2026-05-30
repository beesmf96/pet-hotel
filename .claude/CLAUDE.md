# Pet Hotel — Agent Reference

A pet boarding marketplace: customers search, book, and review stays; owners manage listings via a Filament panel. Infrastructure and booking flow are complete; some features are stubs.

## Stack

| Layer | Detail |
|-------|--------|
| PHP framework | Laravel 13.8 |
| Frontend | Vue 3 + Vite 8 + Tailwind CSS v4 |
| SPA bridge | Inertia.js 3.1 — no REST API, no Axios |
| Admin UI | Filament v4 (two panels) |
| Auth | Sanctum cookie SPA — no API tokens in use |
| Database | PostgreSQL 16 (Docker) / SQLite (local) |
| Queue | Database driver; Redis configured but idle |
| Package manager | Bun — never npm or pnpm |
| Formatter | Laravel Pint — no pint.json, uses framework defaults |
| Test runner (PHP) | PHPUnit 12 |
| Test runner (JS) | Vitest 4 |

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

## PHP Conventions

**Models**
- Use PHP 8.3 promoted-property / attribute syntax for `$fillable`/`$casts` where applicable.
- `$table` is only declared when non-standard (`PetHotelPricing` → `pet_hotel_pricing`).
- Availability side-effects live in `Booking::booted()` only — on `updating`, spots are adjusted; on `updated`, notification jobs fire. Never replicate this in controllers or services.

**Authorization**
- Policies (`BookingPolicy`, `PetPolicy`) for resource-level access. Call `$this->authorize()` in controllers.
- Implicit ownership (user edits own profile/pets) is acceptable without a policy, but ownership must be asserted explicitly — never rely on route binding alone.

**Validation**
- All non-trivial POST/PATCH use Form Request classes in `app/Http/Requests/`.
- Zero validation logic in controllers.

**Controllers**
- Always return `Inertia::render('PageName', $props)`, `redirect()`, or `back()`.
- Never `response()->json()` for customer-facing routes.
- Eager-load every relationship the page needs — N+1 queries are bugs.

**Dates**
- Use Carbon. Cast date columns (`check_in`, `check_out`, `date`) to `'date'` or `'datetime'` in `$casts`. No raw string comparison with dates.

**Soft Deletes**
- Not used. Hard deletes with `onDelete('cascade')` FKs.

## Vue / Inertia Conventions

**State**
- No Pinia or Vuex. All state is Inertia page props or local `ref()`/`reactive()`. Derived state is always a `computed()`.

**Forms**
- Always `useForm()` from `@inertiajs/vue3`. Never `fetch` or `axios` directly.

**Navigation**
- `router.visit()` or `<Link>` from Inertia. Never `window.location`.

**Layouts**
- Declared inline via `defineOptions({ layout: AppLayout })` in `<script setup>`.
- `AppLayout` for authenticated pages, `AuthLayout` for guest pages.

**Language**
- Plain JavaScript — no TypeScript anywhere.

**UI**
- No component library (no shadcn, no Headless UI). All UI is custom Tailwind utility classes.
- Tailwind CSS v4 with no `tailwind.config.js`.
- Leaflet for maps (`HotelMap.vue`). No other mapping lib.

## Filament Conventions

- **Admin panel** at `admin.pet-hotel.local` — requires `is_admin = true`.
- **Hotel owner panel** at `owner.pet-hotel.local` — requires `ownedHotels()->exists()`.
- Resources auto-discovered from `app/Filament/Resources/` (admin) and `app/Filament/HotelOwner/Resources/` (owner).
- No custom themes — amber (admin) and teal (owner) colour tokens only.

## Testing Conventions

- Feature tests: `RefreshDatabase` + in-memory SQLite (never PostgreSQL in tests).
- All test data via model factories. Seeders are not used in tests.
- Auth context: `actingAs(User::factory()->create())`.
- Isolate side-effects: `Queue::fake()` and `Mail::fake()` whenever dispatching jobs or mail.
- Inertia assertions: `assertInertia(fn ($page) => $page->component('...')->has('prop'))`.

## What Is Not Yet Built

- `HotelAvailabilityController` — stub, not wired
- `NotificationController` — partial
- Hotel owner Filament panel — only `BookingResource` exists
- No `routes/api.php` / no API endpoints
- No payment integration
- Vitest coverage is minimal (2 files: `HotelMap.test.js`, `HotelProfilePage.test.js`)
