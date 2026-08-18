---
title: Deployment Checklist
description: Configuration and infrastructure that must be in place before go-live, beyond what the repository can enforce.
badges: Operations
order: 50
---

# Deployment Checklist

Everything that has to be true before Pet Hotel is reachable from the public
internet, and that **cannot be enforced from the repository**.

The application code was audited against the OWASP Top 10 on 2026-08-01 and the
code-side fixes are merged (PRs #10 and #11; findings in
`.claude/plans/plan-owasp-hardening.md`). What remains below is configuration set
on the host. No test, lint, or CI job can catch a mistake here — that is the whole
reason this file exists.

---

## 1. Blockers — do not go live without these

### `APP_DEBUG=false`

**The single most dangerous setting in this list.**

With debug on, any unhandled exception renders a full stack trace to whoever
triggered it — including environment variables and database credentials. An
attacker does not need to find a vulnerability; they only need to cause an error.

```bash
APP_ENV=production
APP_DEBUG=false
```

Both tracked env files (`.env.example`, `.env.docker`) ship `APP_DEBUG=true`,
which is correct for local work. Production must override it.

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Trigger a deliberate 500 on the live site and confirm you get a generic
      error page, not a stack trace

### `APP_KEY`

Laravel encrypts session and cookie data with this. It is empty in both tracked
env files.

```bash
php artisan key:generate
```

- [ ] A real `APP_KEY` is set
- [ ] It is **not** the same value as any other environment
- [ ] It is backed up somewhere — rotating it invalidates every existing session
      and every encrypted column

### `SESSION_SECURE_COOKIE=true`

`config/session.php` reads this with **no default**, so leaving it unset ships
the session cookie without the `Secure` flag. The cookie then travels over plain
HTTP if a connection is ever downgraded, exposing live sessions.

```bash
SESSION_SECURE_COOKIE=true
```

- [ ] Set to `true` (requires working HTTPS first)

### HTTPS

- [ ] TLS certificate installed and valid
- [ ] Plain HTTP redirects to HTTPS
- [ ] Confirm the `Strict-Transport-Security` header appears — `SecurityHeaders`
      middleware emits it automatically once `$request->isSecure()` is true, and
      stays silent before then

> HSTS is hard to walk back: browsers cache `max-age=31536000` for a year. Only
> let it go live once the certificate is confirmed working.

### Real hostnames

The two Filament panels hardcode `.local` domains in
`app/Providers/Filament/*PanelProvider.php`:

```php
->domain('admin.pet-hotel.local')
->domain('owner.pet-hotel.local')
```

These will not resolve in production. They also feed the CSP host check in
`SecurityHeaders::isFilamentHost()`, so a stale value means the panels get the
customer-facing policy and Alpine breaks once CSP enforces.

- [ ] Panel domains updated to real hostnames
- [ ] `APP_URL` set to the real public URL
- [ ] `SESSION_DOMAIN` set so sessions are shared across the panel subdomains
- [ ] `GOOGLE_REDIRECT_URI` updated, and the same URL registered in the Google
      Cloud console — OAuth fails outright if these disagree

---

## 2. Should be done before real users

### Secrets

- [ ] `DB_PASSWORD` is not `secret` (the local placeholder in `.env.docker`)
- [ ] `GOOGLE_CLIENT_SECRET` set from the Google Cloud console
- [ ] `REDIS_PASSWORD` set if Redis is network-reachable
- [ ] No production secret is committed. `.env` and `.env.production` are
      gitignored; `.env.docker` is deliberately **not** — it is a tracked
      template, so never put a real secret in it

### Mail

`.env.example` defaults to `MAIL_MAILER=log`, which silently writes mail to a
file instead of sending it. Password resets and booking notifications would
appear to work while nobody receives anything.

- [ ] A real mail transport configured
- [ ] Send a live password reset and confirm it arrives

### Queue

`SendBooking*Notification` jobs are dispatched to the queue.

- [ ] `QUEUE_CONNECTION=redis` and Redis reachable
- [ ] A queue worker runs under a supervisor and restarts on failure — without
      one, bookings succeed but no notification is ever sent
- [ ] Deploys run `php artisan queue:restart`

### Production caches

- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `bun run build` — the built manifest must exist, or every Inertia page 500s
- [ ] `php artisan migrate --force`

### Storage

- [ ] `php artisan storage:link` — pet photos 404 without it
- [ ] `storage/` and `bootstrap/cache/` writable by the web user

---

## 3. Content Security Policy rollout

The CSP ships in **report-only** mode (`CSP_MODE=report`), so browsers report
violations without blocking anything. It protects nothing until enforced.

- [ ] Deploy with `CSP_MODE=report`
- [ ] Let it run against real traffic — clicking around yourself will not hit
      every page
- [ ] Watch `storage/logs` for `CSP violation` entries
- [ ] Widen `SecurityHeaders::contentSecurityPolicy()` for legitimate sources only
- [ ] When the log is quiet, set `CSP_MODE=enforce`
- [ ] Re-check the maps, both Filament panels, and photo upload afterwards

Detail and the current policy: `.claude/plans/plan-owasp-hardening.md`.

---

## 4. Repository settings

- [ ] **Branch protection on `main`**, requiring `Backend (PHPUnit + coverage)`,
      `Frontend (ESLint + Vitest)`, and `Security (dependency audit)`

Currently unprotected, so a red build can merge. PRs #9, #10, and #11 all could
have. Needs admin rights on `beesmf96/pet-hotel`; see
`.claude/plans/plan-backend-coverage-followups.md`.

---

## 5. Known gaps

Not blockers, but worth knowing you are accepting them:

- **No security event logging** (OWASP A09) — failed logins, password resets, and
  admin actions are not recorded, so there is nothing to investigate with after an
  incident
- **`style-src 'unsafe-inline'`** — the weakest part of the CSP. Vue emits
  component styles as inline blocks; removing it needs nonces or hashes
- **No payment integration** — nothing to secure yet, but that changes the moment
  it lands
- **`is_admin` is a boolean, not roles** — fine at current scale

---

## Post-deploy verification

```bash
# No stack traces
curl -s https://YOUR_DOMAIN/nonexistent | grep -ci "stack trace"   # expect 0

# Security headers present
curl -sI https://YOUR_DOMAIN/ | grep -iE "strict-transport|x-frame|content-security"

# Session cookie is Secure and HttpOnly
curl -sI https://YOUR_DOMAIN/ | grep -i "set-cookie"
```

- [ ] Register, verify email, book, and cancel — as a real user would
- [ ] Google sign-in works against the production redirect URI
- [ ] Both Filament panels load and are reachable only by authorised accounts
