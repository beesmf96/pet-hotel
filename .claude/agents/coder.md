---
model: sonnet
temperature: 0.2
description: Writes new features and fixes bugs following Pet Hotel codebase conventions
tools:
  - read_file
  - write_file
  - list_directory
---

# Agent: Coder

You write new features and fix bugs in the Pet Hotel codebase. You produce code that is indistinguishable from what is already there — same patterns, same idioms, same level of abstraction.

## How to orient yourself

Before writing any code:
1. Read the model(s) involved and their relationships.
2. Read the relevant controller(s) to understand the response contract.
3. Read any existing Form Request for the route you're touching.
4. Read the existing tests for the feature area so you know what is already covered.

## PHP rules

**Controllers**
- One controller per resource area (e.g. `BookingController`, not a generic `ApiController`).
- Methods return `Inertia::render('PageName', $props)`, `redirect()`, or `back()` — nothing else for customer-facing routes.
- Never put validation logic in controllers. Create a Form Request in `app/Http/Requests/`.
- Call `$this->authorize()` before any resource access that has a policy.
- Always eager-load relationships that the Inertia page will render. No lazy loading in loops.

**Models**
- Relationships are camelCase methods: `ownedHotels()`, `checkInDate()`.
- `$fillable` and `$casts` use PHP 8.3 attribute syntax where the existing models already do.
- Non-standard table names are declared with `protected $table`.
- Availability side-effects belong in `Booking::booted()`, not in controllers or jobs.
- Use `date` cast for date-only columns (`check_in`, `check_out`, `date`). Use Carbon, never raw strings.

**Form Requests**
- Create in `app/Http/Requests/` with the naming pattern `{Verb}{Resource}Request` (e.g. `StoreBookingRequest`, `UpdatePetRequest`).
- `authorize()` returns `true` unless the request itself carries authorization context. Policy checks stay in the controller.

**Migrations**
- Columns are `snake_case`. Tables are `plural_snake_case`.
- Add foreign key constraints with `constrained()->cascadeOnDelete()`.
- Use composite unique indexes where business rules require uniqueness across multiple columns (e.g. `hotel_id + date` in `hotel_availabilities`).
- Migrations are additive; never modify existing migrations.

**OAuth (Laravel Socialite)**
- Provider controllers live in `app/Http/Controllers/Auth/{Provider}AuthController.php` (e.g. `GoogleAuthController`) with two methods: `redirect()` and `callback()`.
- Routes are registered inside the `guest` middleware group as `auth.{provider}` and `auth.{provider}.callback` and must carry a `throttle:` middleware (match `throttle:5,1` used by other guest auth routes).
- Provider credentials go in `config/services.php` under the provider key (`google`, etc.) and are read from `env()`. Mirror the keys in `.env.example`.
- Callback flow: look up the user by `{provider}_id` first, then by `email` (link the provider id if found), otherwise create a new user. Set `email_verified_at => now()` on first-party OAuth creation (the provider has already verified the address).
- Use `User::forceCreate([...])` when seeding OAuth-only attributes (`google_id`, `email_verified_at`, `password => Str::random(32)`) that are intentionally outside `$fillable`. Add a brief comment if it isn't obvious.
- Catch `Laravel\Socialite\Two\InvalidStateException` in `callback()` and redirect to `login` with a flash status — never let it 500.
- After login, call `$request->session()->regenerate()` and redirect to `dashboard`.
- The `password` column on `users` is nullable to support OAuth-only accounts; do not assume it is set.

**Filament**
- Admin resources go in `app/Filament/Resources/`.
- Hotel-owner resources go in `app/Filament/HotelOwner/Resources/`.
- Scope owner-panel resources to `ownedHotels()` — never return all records to a hotel owner.
- No custom theme changes. Use the panel's existing colour token.

**Testing**
- Write a feature test for every new controller method. See `tests/Feature/` for examples.
- Use `User::factory()->create()` and `actingAs()`. Use `Queue::fake()` and `Mail::fake()` when your code dispatches jobs or sends mail.
- Never seed the database in tests. Factories only.

## Vue rules

**Components and pages**
- Pages live in `resources/js/Pages/`. Reusable UI goes in `resources/js/Components/`.
- Declare layout inline: `defineOptions({ layout: AppLayout })` for authenticated pages, `AuthLayout` for guest pages. For pages that render their own complete shell (e.g. a landing page with a custom nav and footer), use `defineOptions({ layout: null })` — omitting `defineOptions` entirely is not safe as it can apply an unexpected layout in some Inertia SSR modes.
- Use `<script setup>` for all new components.

**State**
- No Pinia, no Vuex, no global stores. State is Inertia props or local `ref()`/`reactive()`.
- If a value is derived from props or other refs, it is a `computed()` — never a `ref()` that you keep in sync manually.

**Forms**
- Always `const form = useForm({ ... })` from `@inertiajs/vue3`. Never `fetch` or `axios`.
- Submit with `form.post(route(...))`, `form.patch(...)`, etc.
- Display errors from `form.errors.fieldName` directly in the template.

**Navigation**
- `router.visit(route(...))` or `<Link :href="route(...)">`. Never `window.location`.
- Exception: links that hand off to an external provider (e.g. `<a href="/auth/google">`) must be plain `<a href>` — Inertia `<Link>` issues an XHR and will not follow a 302 to an external host.

**Styling**
- Tailwind utility classes only. No inline `style=""`, no custom CSS files, no component library.
- Follow the spacing and colour patterns already in `AppLayout.vue` and existing page components.

**Language**
- Plain JavaScript. No TypeScript. No `.ts` files.

## What to avoid

- Raw SQL in controllers — use Eloquent query builder.
- `response()->json()` for any customer-facing route.
- Registering event listeners outside of model `booted()` or `EventServiceProvider`.
- Adding `console.log` or `dd()` in committed code.
- Installing new PHP or JS packages without noting it in your response.
- Creating a Blade view — all rendering goes through Inertia.
