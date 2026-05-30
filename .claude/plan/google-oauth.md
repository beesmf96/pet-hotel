# Google OAuth Login — Pet Owner (Customer)

## Context

The customer-facing login at `/login` currently supports only email + password. We want to add "Sign in with Google" so users can authenticate via their Google account — click button → Google OAuth page → redirect back to `/dashboard`. This covers both new sign-ups and existing users logging in.

The scope is **customer accounts only** (pet owners). The Filament admin/hotel-owner panels are out of scope.

---

## Implementation Plan

### 1. Install Laravel Socialite

```bash
composer require laravel/socialite
```

No config publish needed — Socialite auto-discovers via the package.

### 2. Add Google credentials to config

In `config/services.php`, add under the existing array:

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],
```

Add to `.env` (and `.env.example`):

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

> **Note:** Google Cloud Console does not support `.local` TLDs. Use `http://localhost/auth/google/callback` as the authorized redirect URI — Google allows `localhost` for development. Access the app via `http://localhost` when testing the OAuth flow (nginx already responds on localhost).

### 3. Migration — add `google_id` + make `password` nullable

New migration: `add_google_id_to_users_table`

```php
$table->string('google_id')->nullable()->unique()->after('email');
$table->string('password')->nullable()->change();  // allow OAuth-only accounts
```

### 4. Update `User` model

- Add `google_id` to the `#[Fillable]` attribute alongside existing fields.
- No other changes needed — `password` cast stays as `'hashed'` (Laravel skips hashing `null`).

File: `app/Models/User.php:17`

```php
#[Fillable(['name', 'email', 'password', 'phone', 'preferred_location', 'google_id'])]
```

### 5. Create `GoogleAuthController`

New file: `app/Http/Controllers/Auth/GoogleAuthController.php`

Two methods:

**`redirect()`** — sends user to Google:
```php
return Socialite::driver('google')->redirect();
```

**`callback()`** — handles the return:
1. `Socialite::driver('google')->user()` to get the Google user.
2. Find existing user by `google_id` OR by `email`.
3. If found by email but no `google_id` → link by setting `google_id`.
4. If not found → create new user (name, email, google_id, `email_verified_at` = now — Google already verified it).
5. `Auth::login($user, remember: true)` + `session()->regenerate()`.
6. Redirect to `/dashboard`.

Handle `\Laravel\Socialite\Two\InvalidStateException` → redirect back to `/login` with an error flash.

### 6. Add routes

In `routes/web.php`, inside the `middleware('guest')` group:

```php
Route::get('/auth/google',          [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
```

### 7. Update Login.vue and Register.vue

Add a Google button above (or below, with a divider) the existing form in both pages.

Pattern (no Inertia form — plain anchor link):

```html
<!-- Divider -->
<div class="relative my-4">
  <div class="absolute inset-0 flex items-center">
    <div class="w-full border-t border-gray-200"></div>
  </div>
  <div class="relative flex justify-center text-xs text-gray-400">
    <span class="bg-white px-2">or</span>
  </div>
</div>

<!-- Google button -->
<a
  href="/auth/google"
  class="flex items-center justify-center gap-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
>
  <img src="/images/google-logo.svg" alt="" class="w-4 h-4" />
  Continue with Google
</a>
```

Add the Google logo SVG to `public/images/google-logo.svg` (the standard coloured `G` mark — a small static file).

---

## Files Changed

| File | Action |
|------|--------|
| `composer.json` | `laravel/socialite` added |
| `config/services.php` | Google driver config |
| `.env` / `.env.example` | OAuth credentials |
| `database/migrations/…_add_google_id_to_users_table.php` | New migration |
| `app/Models/User.php` | `google_id` in `#[Fillable]` |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | New controller |
| `routes/web.php` | Two new routes in guest group |
| `resources/js/Pages/Auth/Login.vue` | Google button |
| `resources/js/Pages/Auth/Register.vue` | Google button |
| `public/images/google-logo.svg` | Google G icon |

---

## Google Cloud Console Setup (one-time, by you)

1. Create a project at console.cloud.google.com → **APIs & Services → Credentials**.
2. Create **OAuth 2.0 Client ID** (Web application).
3. Authorized redirect URIs: `http://localhost/auth/google/callback` (local) + your production URL.
4. Copy Client ID and Secret into `.env`.

---

## Verification

1. `php artisan migrate` — confirm `google_id` column and nullable `password`.
2. Set real Google credentials in `.env`.
3. Visit `/login` → click "Continue with Google" → Google auth page opens.
4. Complete auth → lands on `/dashboard` as a logged-in user.
5. Visit `/login` again → same Google account → logs in without creating a duplicate user.
6. Existing email+password user → Google sign-in with same email → accounts are linked, no duplicate.
7. Run `composer test` — existing auth tests still pass (password nullable doesn't break them since factories set a password).
