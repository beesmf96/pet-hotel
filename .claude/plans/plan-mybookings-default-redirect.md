---
plan: mybookings-default-redirect
status: done
branch: feature/mybookings-default-redirect
pr: https://github.com/beesmf96/pet-hotel/pull/4
implemented: 2026-05-30
---

# Feature: My Bookings as Default Post-Login Page

## What & Why
After a pet-owner logs in, they currently land on `/dashboard` and see a "Dashboard" link in the nav. The dashboard is not the most useful default landing experience — My Bookings is. This change removes the Dashboard nav link and redirects users to `/bookings` upon login so they immediately see their booking history.

## Scope
- Remove the "Dashboard" `<a>` link from the authenticated nav in `AppLayout.vue`
- Change post-login redirect in `LoginController::store()` from `/dashboard` to `/bookings`
- Change post-login redirect in `GoogleAuthController::callback()` from `route('dashboard')` to `route('bookings.index')`
- Change post-registration redirect in `RegisterController::store()` from `/dashboard` to `/bookings`
- Update Vitest test in `resources/js/tests/Landing.test.js` if it asserts dashboard-related nav behaviour
- Update PHP feature tests that assert post-login redirect to `/dashboard`

## Out of Scope
- Removing the `/dashboard` route or `DashboardController` — the route stays but is no longer the default landing
- Changes to the Filament admin or hotel-owner panels
- Any changes to what the Dashboard page renders

## Technical Approach

### Backend
**Files to modify:**

1. `app/Http/Controllers/Auth/LoginController.php` (line 35)
   - Change `redirect()->intended('/dashboard')` → `redirect()->intended('/bookings')`

2. `app/Http/Controllers/Auth/GoogleAuthController.php` (line 50)
   - Change `redirect()->route('dashboard')` → `redirect()->route('bookings.index')`

3. `app/Http/Controllers/Auth/RegisterController.php` (line 34)
   - Change `redirect('/dashboard')` → `redirect('/bookings')`

No new routes, migrations, models, or packages needed.

### Frontend
**Files to modify:**

1. `resources/js/Layouts/AppLayout.vue` (line 28)
   - Remove the `<a href="/dashboard" ...>Dashboard</a>` element from the authenticated nav block

No new components needed. No layout changes beyond the nav link removal.

### Tests to update
- `tests/Feature/Auth/AuthTest.php` — any assertion that post-login redirects to `/dashboard` must be updated to assert `/bookings`
- `tests/Feature/Auth/GoogleAuthTest.php` — same for the OAuth callback success assertion
- Check `resources/js/tests/Landing.test.js` — update if it asserts the Dashboard link is present for authenticated users

## Acceptance Criteria
- [ ] Nav bar for logged-in users no longer shows a "Dashboard" link
- [ ] Logging in via email/password lands the user on `/bookings`
- [ ] Logging in via Google OAuth lands the user on `/bookings`
- [ ] Registering a new account lands the user on `/bookings`
- [ ] Direct navigation to `/dashboard` still works (route is preserved)
- [ ] All PHP tests pass (`composer test`)
- [ ] Frontend linting passes (`bun run lint`, if configured)

## Edge Cases
- `redirect()->intended('/bookings')` respects a stored intended URL (e.g. if the user was trying to reach `/bookings/5` before being redirected to login, they will land there after login — correct behaviour, no change needed).
- New users registering have no bookings yet; `MyBookingsPage` already handles the empty state with a "Find a Hotel" CTA.

## Open Questions
None — scope is fully defined.
