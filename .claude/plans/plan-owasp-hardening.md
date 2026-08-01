---
plan: owasp-hardening
status: draft
branch: ~
pr: ~
implemented: ~
---

# OWASP Top 10 Hardening — Pre-Public-Deploy

## What & Why

The app is heading for a public deployment. This is an audit of the current
codebase against the OWASP Top 10 (2021), plus the work needed to close the gaps.

Audit performed 2026-08-01 against `main` @ `3b03535`.

**Headline:** the application code is in good shape. Authorization, query
parameterization, and mass-assignment protection are all done correctly and were
clearly thought about. Essentially all of the real risk is in *deployment
configuration* and *dependency freshness* — not in the business logic.

Nothing here is a known-exploited hole in your own code. But several items below
would be genuinely dangerous the moment this is reachable from the public internet.

## What was checked

All 18 controllers, 5 Form Requests, 2 policies, 10 models, `routes/web.php`,
`bootstrap/app.php`, the Inertia middleware, `config/session.php`,
`docker/nginx/conf.d/*`, tracked env files, and both dependency trees.

---

## Findings

Severity is "risk once publicly deployed", not "risk today on localhost".

### P1 — Dependencies: 34 advisories across 15 production packages

`composer audit` reports 34 advisories affecting 15 packages. Confirmed with
`--no-dev` that **all of them are in production dependencies**, not tooling:

| Advisories | Package |
|---|---|
| 7 | `guzzlehttp/guzzle` |
| 5 | `symfony/html-sanitizer` |
| 4 | `guzzlehttp/psr7` |
| 3 | `laravel/framework` |
| 3 | `filament/filament` |
| 2 | `symfony/routing`, `symfony/mime` |
| 1 each | `symfony/{polyfill-intl-idn,mailer,http-kernel,http-foundation}`, `phpseclib/phpseclib`, `filament/{tables,infolists,actions}` |

Two of the `symfony/routing` ones (CVE-2026-45065, CVE-2026-48784) are URL
generation bypasses — directly relevant to a public app.

- [ ] `composer update` and re-audit
- [ ] Confirm nothing breaks — the 315-test suite is the safety net here

**Maps to:** A06 Vulnerable and Outdated Components

### P1 — No security headers on any response

`docker/nginx/conf.d/{web,admin,owner}.conf` set only `Cache-Control` on static
assets. There is no CSP, HSTS, `X-Frame-Options`, `X-Content-Type-Options`, or
`Referrer-Policy` anywhere in the stack.

Consequence: the admin and owner Filament panels are clickjackable, and there is
no defence-in-depth against XSS.

- [ ] Add `X-Frame-Options: DENY` (or CSP `frame-ancestors 'none'`)
- [ ] Add `X-Content-Type-Options: nosniff`
- [ ] Add `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] Add HSTS — **only after** HTTPS is confirmed working, it is hard to walk back
- [ ] Scope a CSP. Inertia + Vite makes this fiddly; start `Report-Only`

Worth deciding whether these live in nginx or in Laravel middleware. Middleware
travels with the app if the deployment target isn't this nginx config.

**Maps to:** A05 Security Misconfiguration

### P1 — Session cookies are not marked `Secure`, and `APP_DEBUG=true`

`config/session.php:172` reads `env('SESSION_SECURE_COOKIE')` with no default, so
it is `null` unless explicitly set — the session cookie ships without the `Secure`
flag. Both tracked env files also carry `APP_DEBUG=true` and `APP_ENV=local`.

These are correct for local dev. On a public host, `APP_DEBUG=true` exposes stack
traces, env vars, and DB credentials on any unhandled error.

- [ ] Production env: `APP_DEBUG=false`, `APP_ENV=production`
- [ ] Production env: `SESSION_SECURE_COOKIE=true`
- [ ] Consider `SESSION_ENCRYPT=true`
- [ ] Generate a real `APP_KEY` (tracked files have it empty)
- [ ] `SESSION_SAME_SITE` is `lax` — fine, but confirm it against the OAuth callback

**Maps to:** A02 Cryptographic Failures, A05 Security Misconfiguration

### P2 — Password reset endpoints have no rate limit

`routes/web.php:36,39` — `password.email` and `password.update` are the only
unauthenticated POST routes with no `throttle` middleware. Login, register, Google
OAuth, review submission, and verification resend all have one.

Two consequences: unlimited reset-token guessing on `password.update`, and
`ForgotPasswordController:26-28` returns a different response for known vs unknown
emails, so `password.email` is an unthrottled **user enumeration oracle**.

- [ ] Add `throttle:5,1` to both routes
- [ ] Consider always returning the generic success message regardless of whether
      the email exists

**Maps to:** A07 Identification and Authentication Failures

### P2 — Google OAuth links accounts on unverified email

`GoogleAuthController:32-35` — when no `google_id` matches, it looks the user up by
email and links the Google identity to that existing account. It never checks
whether Google considers the email verified.

For Google specifically this is low risk, since Google verifies its own addresses.
It becomes an account-takeover path the moment a second Socialite provider is added
that doesn't (the docs already flag "Google is the only provider configured" as a
known limitation). Cheap to fix now, easy to forget later.

- [ ] Check the `email_verified` claim before linking to an existing account
- [ ] Or require the user to be logged in to link a provider

**Maps to:** A07 Identification and Authentication Failures

### P3 — `.env.docker` is both gitignored and tracked

`.gitignore:8` lists `.env.docker`, but `git ls-files` shows it is tracked —
gitignore does not untrack a file already committed.

No live secrets are exposed today: `APP_KEY` is empty and `DB_PASSWORD=secret` is a
local value. The risk is the trap it sets — the file *looks* ignored, so a real
secret added to it later gets committed silently.

- [ ] Either `git rm --cached .env.docker`, or drop it from `.gitignore` so its
      tracked status is honest

**Maps to:** A05 Security Misconfiguration

### P3 — No security event logging

No logging on failed logins, password resets, or admin actions. Nothing to
investigate with after an incident.

- [ ] Log auth failures and admin panel mutations

**Maps to:** A09 Security Logging and Monitoring Failures

---

## What is already correct

Worth recording so nobody "fixes" it later:

- **A01 Broken Access Control — clean.** `BookingPolicy` and `PetPolicy` are
  enforced on every mutating route. `NotificationController` scopes every lookup
  through `$request->user()->notifications()`, so the `{id}` parameter is not an
  IDOR. `ReviewController:49-54` constrains by `user_id`, `hotel_id`, `status`, and
  `doesntHave('review')` before creating. `BookingController:32` resolves pets
  through the user relation.
- **A01 privilege escalation — blocked.** `is_admin` is absent from `User`'s
  `#[Fillable]`, and `UpdateUserRequest` allows only three fields, so the profile
  endpoint cannot self-promote to admin.
- **A03 SQL injection — clean.** Every `whereRaw`/`orderByRaw` was checked
  individually. `HotelSearchController:24` uses a bound `?` placeholder;
  lines 51-56 are constant strings with no interpolation; `applyDistanceSort:77-80`
  casts to `float` *and* binds. No string interpolation into SQL anywhere.
- **A03 XSS — clean.** No `v-html` or `innerHTML` in `resources/js/`. Vue escapes
  by default.
- **A03 CSRF — covered.** All state-changing routes are in the `web` group.
- **File uploads** are validated with `image` and `max:2048`.
- **JS dependency advisories are not shipped.** `bun audit` reports 16 vulns
  (1 critical, 10 high), but every affected package — `shell-quote` via
  `concurrently`, `vite`, `jsdom`, `undici` — is a `devDependency`. None reach a
  production bundle. Worth fixing for build-machine hygiene, not deploy-blocking.

## Suggested order

1. `composer update` + re-audit — highest risk, and the test suite de-risks it
2. Production env config (`APP_DEBUG`, `SESSION_SECURE_COOKIE`, `APP_KEY`)
3. Security headers
4. Password reset throttling + enumeration
5. OAuth `email_verified`, `.env.docker`, logging

1-2 are deploy blockers. 3-4 should land before real users. 5 is follow-up.

## Making it repeatable

The audit above is a snapshot; dependency risk regenerates continuously.

- [ ] Add `composer audit` and `bun audit` to `.github/workflows/ci.yml`
- [ ] Decide whether they block or just warn — `bun audit` is currently all-dev
      noise, so blocking on it would be false-positive heavy from day one
- [ ] Consider Dependabot for automated bumps

## Open Questions

- Headers in nginx or Laravel middleware? Depends on whether production uses this
  compose stack or a managed host.
- Is `composer update` (respecting constraints) enough, or do any of the 34 need a
  major bump? Unknown until it is run.
- Should the CI audit gate block? Leaning warn-only initially, matching the
  deliberately-loose `MIN_COVERAGE=95` decision in
  [[plan-backend-coverage-followups]].
