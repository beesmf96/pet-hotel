# CLAUDE.md

**Pet Hotel** — a pet boarding marketplace. Customers search, book, and review
boarding stays; hotel owners manage listings through a Filament panel. Feature
roadmap and status: `docs-src/tasks.md`.

This file records what you cannot infer from the code: workflow, decisions, and
traps. For everything else — naming, structure, style — match the surrounding code.

## Stack

Laravel 13 · Vue 3 + Vite + Tailwind v4 (no `tailwind.config.js`) · Inertia 3 · Filament 4
(two panels) · Sanctum cookie SPA + Google OAuth via Socialite · PostgreSQL in Docker, SQLite
locally and in every test · Bun (never npm/pnpm) · Pint + ESLint/Prettier · PHPUnit 12 · Vitest 4.

No component library, no TypeScript, no Pinia/Vuex, no `routes/api.php`, no payments,
no Blade views outside the Filament panels. Google is the only Socialite provider.

## Domain model

```
users ──< pets
users ──< bookings ──> pets
users >─< pet_hotels  (pivot: hotel_owner, has role column)
pet_hotels ──< facilities, photos, pricing (per pet_type), bookings
pet_hotels ──1 policies
pet_hotels ──< hotel_availabilities   (one row per date, available_spots INT)
bookings   ──1 reviews
```

Hard deletes with cascading FKs throughout.

## Commands

```bash
composer dev          # PHP server + queue worker + Pail + Vite
composer test         # Clears config cache, then PHPUnit
bun run test          # Vitest
bun run lint          # ESLint (bun run lint:fix to autofix)
vendor/bin/pint       # PHP formatter
php docs-src/build.php # renders stakeholder docs → docs/ (the GitHub Pages site)
```

Docker: `docker compose up -d` (add `--profile dev` for the Vite container). **Always pass
`--user appuser` to `docker compose exec`** — without it commands run as root and leave
root-owned files you cannot edit. Repair with
`docker compose exec -u root app chown -R 1000:1000 /var/www`.

Hosts file: `127.0.0.1 web.pet-hotel.local mailpit.local`. Google rejects `.local`
redirect URIs, so Google login is tested end to end on `http://localhost`.

**Queue worker.** Booking notifications are queued jobs and are the only thing that tells a
customer their booking was requested, confirmed, or cancelled. Without a worker, bookings
still succeed and no one is notified — there is no error. Docker runs one as the `queue`
service; any hosted deployment needs its own long-running worker process.

## Traps and decisions

- **Availability side-effects live in `Booking::booted()` only** — spots adjust on
  `updating`, notification jobs fire on `updated`. Never replicate this elsewhere.
- **Two Filament panels routed by path** — `/admin` requires `is_admin`, `/owner` requires
  `ownedHotels()->exists()`. Do not add `->domain()` to a panel without updating
  `SecurityHeaders::isFilamentRequest()`, which scopes the `'unsafe-eval'` CSP relaxation.
  Owner-panel resources must scope `getEloquentQuery()` to `ownedHotels()`.
- **All uploads go through `config('filesystems.photos')`.** Never name a disk literally at
  an upload site or build a URL from a different disk. `PHOTO_DISK` must be `s3` on
  ephemeral hosting. `SecurityHeaders` reads the same config for the CSP `img-src`.
- **Customer-facing routes never return `response()->json()`.** The two exceptions are
  XHR-backed widgets: `hotels.availability` and `notifications.*`.
- **A null `users.password` is the only signal of an OAuth-only account.** `PasswordController`
  relies on it to skip the `current_password` check. Never test `google_id` instead — a
  user who registered with a password and later linked Google has both. Never write a
  random password for an OAuth account.
- **`password` is outside `$fillable`** — assign it with `forceFill()`; `update()` silently
  drops it. `User::forceCreate()` is reserved for registration and first-party OAuth creation.
- **Guest auth routes carry `throttle:5,1`** — match it on any new guest-facing auth endpoint.
- **The OAuth entry link is a plain `<a href="/auth/google">`** — an Inertia `<Link>` will
  not follow the 302 to Google. Everything else navigates with `<Link>` / `router.visit()`.
- **Every page wraps itself in `<AppLayout>` or `<AuthLayout>`.** `Landing.vue` is the one
  `layout: null` page.

## Testing

`tests/Feature/BookingTest.php` is the canonical shape. Factories only, never seeders
(`HotelAvailability` has none — `create([...])` it; `User::factory()->admin()` and
`->hotelOwner($hotel)` exist). Booking tests assert `available_spots` decrements on confirm
and re-increments on cancel. Filament resources are tested with `Livewire::test(...)` under
`tests/Feature/Filament/`, not over HTTP. Socialite is mocked at the facade — see
`GoogleAuthTest.php`. Vitest specs live in `resources/js/tests/` mirroring `resources/js/`;
when a page gains a prop-driven `v-if` branch, add one test per branch.

## After any implementation task

1. Tests exist for the new behaviour, and `composer test` passes
2. `vendor/bin/pint`; for frontend changes, `bun run lint`
3. A session log entry in `docs-src/log/` (rules: `docs-src/paper-trail.md`). Add an ADR, knowledge
   entry, or intake entry only when the bar there is met. Write an intake entry **before**
   adding any package, binary, image, or action.

The log is the primary source for any recap of past work — read it before git history.

## Docs

`docs/` is the GitHub Pages site (served from `main:/docs`) and holds HTML only — never put
markdown there, and keep its `.nojekyll`. Markdown lives under `docs-src/` and is coder-facing by
default. Only files with `audience: stakeholder` render to `docs/`. Never hand-edit a
generated `.html` — the hand-written exceptions are listed in `STATIC_DOCS` in `docs-src/build.php`.

## Shipping work

```
feature/{name}  ──PR──▶  dev  ──PR (release)──▶  main
```

- Cut `feature/{name}` from `dev` before exploring or editing — never work on `dev` or `main`
- Open a PR to `dev` and leave it for human review — **do not merge**
- `main` only receives a release PR from `dev`, opened when the user asks for one
- Verify before reporting: run the tests, Pint, and the linter, and report the actual output
- Plan files live in `.claude/plans/` as `plan-{name}.md`; start from `_template.md` and
  keep the frontmatter current

CI (`.github/workflows/ci.yml`) runs Pint, PHPUnit with pcov coverage, `composer audit`,
`bun audit`, ESLint, and Vitest on every PR to and push to `main` or `dev`. The backend job
fails below `MIN_COVERAGE` (`95`) — raise it as coverage improves, never lower it.
