---
plan: change-password
status: implemented
branch: feature/change-password
pr: ~
implemented: 2026-08-19
---

# Feature: Change Password

## What & Why

There is no authenticated change-password feature. `UpdateUserRequest` allows only
`name`, `phone`, and `preferred_location`, and the reset routes sit behind `guest`
middleware — so a logged-in user is redirected away from them. The only way to change
a password today is to log out and use the emailed reset link.

Google accounts have it worse. `GoogleAuthController` creates them with
`Str::random(32)`, hashed by `User::casts()`, so they carry a real password hash of a
value nobody knows. They cannot confirm a current password, so any change-password
form that demands one locks them out permanently.

The product decision (2026-08-19): a Google user **can** set a password and afterwards
sign in either way. This matches what `GoogleAuthController` already does in the
reverse direction — a password account that later signs in with Google keeps its
password and gains `google_id`.

## Scope

- Authenticated `PUT /profile/password` for users who have a password: requires
  `current_password`.
- The same route for OAuth-only users (no password): sets a password without a
  current-password check.
- `GoogleAuthController` creates OAuth accounts with `password => null` rather than a
  random hash, so "has no usable password" becomes representable.
- `Profile.vue` grows a second card: "Change Password", or "Set a Password" for
  OAuth-only accounts.
- Tests: feature tests for both paths and the failure modes; a Vitest test for the
  conditional form.
- Corrects the OAuth notes in `.claude/CLAUDE.md` and `.claude/agents/coder.md`, which
  currently describe the random-hash behaviour this replaces.

## Out of Scope

- **No data migration for existing OAuth accounts.** Accounts already created with a
  random hash keep it and will see the "change" form they cannot satisfy. A blanket
  `UPDATE users SET password = NULL WHERE google_id IS NOT NULL` would destroy the
  real password of anyone who registered with one and later linked Google — the two
  cases are indistinguishable in the current schema. Affected users can use the
  forgot-password flow. Only a handful of such accounts exist, all on dev.
- Unlinking a Google account.
- Two-factor auth, password-strength meters, breach checks.
- Email notification on password change (mail is unconfigured on dev).
- Logging out other sessions on change.

## Technical Approach

### Backend

- **Models** — `User` only. No schema change: `users.password` is already nullable.
- **Migrations** — none.
- **`GoogleAuthController`** — `password => null` in the `forceCreate` call for new
  OAuth accounts. Account *linking* is untouched; a user who already has a password
  keeps it.
- **`app/Http/Controllers/Auth/PasswordController.php`** — single `update()` method.
  `password` is outside `$fillable`, so it assigns with `forceFill([...])->save()` as
  `ResetPasswordController` does — `update()` would silently drop it. The `hashed`
  cast handles hashing. Returns `back()->with('success', ...)`.
- **`app/Http/Requests/UpdatePasswordRequest.php`** — `current_password` is
  `['required', 'current_password']` when the user has a password, and omitted when
  they do not. New password: `['required', 'string', 'min:8', 'confirmed']`, matching
  `ResetPasswordController`.
- **Route** — `Route::put('/profile/password', ...)` named `profile.password.update`,
  inside the existing `verified` group next to the other profile routes. Not
  `password.update`, which the reset flow already owns.
- **`UserController@edit`** — passes `hasPassword` (camelCase, as `LandingController`
  does with `featuredHotels`) so the page knows which form to render.
- **Login** — no guard added. Laravel's `BcryptHasher::check()` returns false for a
  null hash, so a null-password account simply cannot log in by password. Locked in
  with a test rather than assumed.

### Frontend

- **`Profile.vue`** — a second card below the existing one, its own `useForm`. The
  current-password field renders only when `hasPassword` is true; heading and button
  read "Change Password" or "Set a Password" accordingly. Submits with
  `form.put('/profile/password')`, resetting fields on success. Errors render from
  `form.errors.*`, matching the existing card.
- **Navigation** — stays on the profile page; success shown inline like "Saved!".

## Acceptance Criteria

- [x] A user with a password can change it with the correct current password
- [x] A wrong current password is rejected with a validation error and no change
- [x] An OAuth-only user (null password) can set one without a current password
- [x] After setting one, that user can log in with email + password
- [x] A null-password account cannot be logged into by password before setting one
- [x] `current_password` is rejected as unnecessary noise, not required, for OAuth users
- [x] Mismatched confirmation and passwords under 8 characters are rejected
- [x] The route is unreachable when unauthenticated (302)
- [x] New Google accounts are created with `password => null`
- [x] `composer test` passes, `vendor/bin/pint` clean, `bun run lint` clean

## Edge Cases

- **Existing random-hash OAuth accounts** — see Out of Scope. They fall into the
  "has password" branch and cannot satisfy it; forgot-password is the escape.
- **Linked accounts** (registered with a password, later signed in with Google) —
  `google_id` is set *and* the password is real, so they must stay in the
  current-password branch. This is why the check is `password === null`, never
  `google_id !== null`.
- **Reusing the same password** — permitted, consistent with the reset flow.
- **`current_password` rule and the session** — Laravel's `current_password` rule
  validates against the authenticated guard's user, so it is correct here without
  passing the user explicitly.

## Open Questions

None. The sign-in model was decided on 2026-08-19: OAuth users may set a password and
afterwards use either method.
