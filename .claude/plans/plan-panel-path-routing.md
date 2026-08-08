---
plan: panel-path-routing
status: implemented
branch: feature/panel-path-routing
pr: 15
implemented: 2026-08-08
---

# Feature: Panel Path Routing

## What & Why

Both Filament panels are pinned to development hostnames — `AdminPanelProvider`
declares `->domain('admin.pet-hotel.local')->path('')` and
`HotelOwnerPanelProvider` declares `->domain('owner.pet-hotel.local')->path('')`.
Those hostnames only resolve through a local hosts-file entry, so on the
deployed app at `https://pet-boarding-prod-rmqeju.laravel.cloud/` no route
matches either panel and every request 404s. The app is on a
`laravel.cloud` subdomain with no custom domain, so `admin.` / `owner.`
subdomains cannot be pointed anywhere — domain-based panels are not an option
until a custom domain exists.

Moving both panels to paths on the single app domain makes them reachable in
production and removes the hosts-file requirement from local development.

## Scope

- Both panel providers routed by path, not domain: `/admin` and `/owner`.
- `SecurityHeaders::isFilamentHost()` reworked to match on path, since the CSP
  relaxation Filament's Alpine needs is currently keyed on hostname and would
  silently stop applying once the domains are gone.
- Tests updated for the new URLs, plus coverage that both panels actually
  respond on the app domain.
- `CLAUDE.md` and `.claude/CLAUDE.md` updated: hosts-file entries for the panel
  subdomains, and the "two Filament panels" domain references.

## Out of Scope

- Every other production-readiness item (object storage for pet photos, queue
  worker, SMTP, production env vars). Those are separate changes; this plan is
  only about panel reachability.
- Custom domain setup. If one is added later, domain-based routing can come
  back — `isFilamentRequest()` is written to still honour panel domains.
- Flipping `CSP_MODE` to `enforce`.

## Technical Approach

### Backend

- `app/Providers/Filament/AdminPanelProvider.php` — drop `->domain(...)`, set
  `->path('admin')`.
- `app/Providers/Filament/HotelOwnerPanelProvider.php` — drop `->domain(...)`,
  set `->path('owner')`.
- `app/Http/Middleware/SecurityHeaders.php` — replace `isFilamentHost()` with
  `isFilamentRequest()`. A panel matches when its domains (if any) contain the
  request host **and** the request path is within the panel path. Keeping the
  domain check means re-adding a custom domain later needs no further edit.

No migrations, no models, no jobs. Both panels already resolve users through
the shared `web` guard, and moving to a single origin means one session cookie
covers the app and both panels — strictly simpler than the subdomain setup.

### Frontend

None. No Vue page links to either panel.

## Acceptance Criteria

- [x] `/admin/login` returns 200 on the app domain
- [x] `/owner/login` returns 200 on the app domain
- [x] The panel subdomains no longer route anywhere
- [x] Filament paths still receive `'unsafe-eval'` in the CSP
- [x] `/` still does **not** receive `'unsafe-eval'`
- [x] `composer test` passes, `vendor/bin/pint` clean

## Edge Cases

- **Path collision.** `routes/web.php` has no `/admin` or `/owner` route, and
  the panels' `/admin/login` does not collide with the app's `/login`.
- **CSP relaxation leaking to the SPA.** The panels no longer have their own
  origin, so the check must be exact — a naive `str_contains` on the path would
  match a hotel slug like `/hotels/admin-kennels`. Matching is anchored with
  `$request->is($path, $path.'/*')`.
- **`->default()` on the admin panel** stays. It sets which panel Filament
  resolves by default; it does not claim `/`.

## Open Questions

None. Path-based routing for both local and production was chosen over a
config-driven dual mode to keep one routing behaviour.
