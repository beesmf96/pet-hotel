---
title: Deployment Checklist
description: Step-by-step runbook for putting Pet Hotel on Laravel Cloud, from the first deploy to go-live.
badges: Operations
order: 50
---

# Deployment Checklist

How to deploy Pet Hotel on [Laravel Cloud](https://cloud.laravel.com), in the
order the Cloud dashboard asks for things. Follow it top to bottom for a new
environment. For an existing environment, use it as a checklist.

Everything below is set in the Cloud dashboard, not in the repository. No test
or CI job can catch a mistake here, which is why this file exists.

The current `dev` environment lives at
`https://pet-hotel-dev-qvhqsv.laravel.cloud` and tracks the `dev` branch.
Production should track `main`.

---

## 1. Before the first deploy (repository side)

These have to be true in the code before Cloud can run it.

- [x] **S3 driver installed.** Cloud object storage is S3-compatible and needs
      the Flysystem adapter. `league/flysystem-aws-s3-v3` has been in
      `composer.json` since 2026-09-16 (intake:
      `docs-src/intake/flysystem-aws-s3-v3.md`). Without it, any bucket-backed
      `PHOTO_DISK` throws on the first upload.

- [ ] `composer.lock` and `bun.lock` are committed and up to date. Cloud reads
      the lock files at build time.
- [ ] The branch you deploy has passed CI.

---

## 2. Create the environment

In Cloud: **Applications → your app → New environment** (or **Replicate** the
`dev` environment and change the branch).

- [ ] Name: `production` (the name becomes part of the free `*.laravel.cloud` URL)
- [ ] Region: same as the `dev` environment
- [ ] Branch: `main`
- [ ] PHP version: 8.4, to match the Docker image (`composer.json` requires `^8.3`)
- [ ] **Push to deploy** stays on. Every merge to `main` then deploys itself.

---

## 3. Attach resources

Click the environment canvas. Each of these injects its own env vars when you
deploy, so do not type database or cache credentials by hand.

- [ ] **Database → Serverless Postgres.** Injects `DB_*`. Then run migrations as
      a deploy command (step 5).
- [ ] **Cache → Laravel Valkey.** Injects `CACHE_STORE`, `REDIS_HOST`,
      `REDIS_PASSWORD`. Sessions and cache both use it (step 4).
- [ ] **Add bucket → Laravel Object Storage.** Visibility **public** (pet and
      hotel photos are shown to everyone; R2 has no per-object ACLs, so this
      is the only thing that makes photo URLs work).
- [ ] **Disk name** `photos`. The field wants 3 to 40 lowercase characters, so
      `s3` is refused. This name matters: Cloud injects
      `LARAVEL_CLOUD_DISK_CONFIG`, a JSON list of buckets, and Laravel's
      `CloudBootstrapper` registers a filesystem disk under each `disk` name at
      boot (also when config is cached). The disk comes with its credentials,
      endpoint, and public `url` already set, so none of the `AWS_*` variables
      are injected or needed. The `s3` disk in `config/filesystems.php` is for
      any other S3-compatible host.
- [ ] **Default disk** on or off does not matter, because step 4 overrides it.
      Cloud injects `FILESYSTEM_DISK=photos` when it is on, and Livewire then
      sends the admin panel's photo uploads straight from the browser to the R2
      endpoint with a presigned URL. That request leaves this origin, so the
      CSP `connect-src 'self'` reports it today and blocks it once enforced.
      `FILESYSTEM_DISK=local` keeps Livewire's temporary files on the container,
      where the app moves them into the bucket itself. Customer pet photos never
      go through Livewire and are unaffected either way.
- [ ] Set `PHOTO_DISK=photos` in step 4 (the disk name, not `s3`). The app builds
      photo URLs and the CSP `img-src` from that disk's `url`, which Cloud fills
      in as `https://<bucket>.laravel.cloud`.

Cloud's own compute is ephemeral: every deploy wipes the container filesystem.
Anything written to `storage/` is lost, which is why photos must go to the bucket.

---

## 4. Environment variables

**Environment → Settings → Environment variables.** Custom values override the
injected ones.

### Must be set

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example       # the real public URL, https
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
CACHE_STORE=redis
PHOTO_DISK=photos                          # the bucket's Disk name (step 3)
FILESYSTEM_DISK=local                      # overrides the injected value (step 3)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
CSP_MODE=report                            # switch to enforce later (step 9)
```

- [ ] `APP_DEBUG=false`. **The single most dangerous setting here.** With debug
      on, any error shows a stack trace with env values and database credentials
      to whoever caused it.
- [ ] `APP_KEY` is set. Cloud generates one on the first deploy. Never copy the
      key from another environment, and keep a backup: rotating it logs out
      every user and breaks every encrypted column.
- [ ] `SESSION_SECURE_COOKIE=true`. `config/session.php` has no default for it,
      so an unset value ships the session cookie without the `Secure` flag.
- [ ] `PHOTO_DISK` set to the bucket's disk name. Step 1 must be done first.
- [ ] `FILESYSTEM_DISK=local`, so Filament uploads stay inside the CSP.
- [ ] `GOOGLE_CLIENT_SECRET` from the Google Cloud console, for a client that
      belongs to **this** environment (step 7).
- [ ] No secret is committed to git. `.env.docker` is tracked on purpose as a
      local template, so never put a real value in it.

### Do not set

- `GOOGLE_REDIRECT_URI`. `config/services.php` defaults to the relative
  `/auth/google/callback`, which Socialite expands against the request host, so
  one code path works on every environment.
- `QUEUE_CONNECTION`, unless you choose the worker cluster route in step 6.
- `DB_*`, `REDIS_*`, `LARAVEL_CLOUD_DISK_CONFIG`, `FILESYSTEM_DISK`. Injected by
  the attached resources.
- `AWS_*`. Cloud never sets them; the bucket arrives as a ready-made disk. They
  only matter for the `s3` disk on a non-Cloud host.

After changing any variable, **redeploy**. Deploys run `config:cache`, so a
change does nothing until the next deploy.

---

## 5. Build and deploy commands

**Environment → Settings → Deployments.**

Build commands (run at image build time, results are kept):

```bash
composer install --no-dev --optimize-autoloader
bun install --frozen-lockfile
bun run build
php artisan optimize
```

Deploy commands (run just before the new release goes live, filesystem changes
are **not** kept):

```bash
php artisan migrate --force
```

- [ ] Build commands as above. `bun run build` is required: without the built
      manifest every Inertia page is a 500.
- [ ] Deploy command is only the migration.
- [ ] **Do not** add `queue:restart`, `storage:link`, or `optimize:clear`. Cloud
      restarts workers itself, the storage symlink does not survive a deploy
      (the bucket replaces it), and `optimize:clear` can break the queue.

---

## 6. Queue worker

`SendBooking*Notification` jobs are queued. They are the only thing that tells a
customer their booking was requested, confirmed, or cancelled. **Without a
worker, bookings still succeed and nobody is notified.** Nothing in Cloud runs a
worker by default.

Pick one:

- [ ] **Managed queue (recommended).** Canvas → **Add compute → Managed queue**.
      Name it `default`, Flex class, 256 MiB, 0 to 3 workers. Deploying sets
      `QUEUE_CONNECTION=cloud` for you. Failed jobs appear under
      **Monitoring → Queues**. Requires `aws/aws-sdk-php`, which the S3
      adapter from step 1 already pulls in.
- [ ] **Or a background process on the App cluster.** Click the App cluster →
      **Background processes → New background process → Queue worker**.
      Connection `redis`, queue `default`, 1 process. Set
      `QUEUE_CONNECTION=redis` in step 4. Cheaper, but shares CPU with web
      traffic and can be cut off if the environment scales to zero.

The app registers no scheduled tasks, so the scheduler toggle stays off.

---

## 7. Domain and HTTPS

Cloud terminates TLS at its edge, redirects HTTP to HTTPS, and issues and renews
the certificate for you. The app's `SecurityHeaders` middleware sees the request
as secure and adds `Strict-Transport-Security` on its own. This was verified on
the `dev` environment.

- [ ] **Environment → Network → Add domain.** Add the DNS records the dashboard
      shows and refresh until the status is **Connected**.
- [ ] Set it as the primary domain.
- [ ] `APP_URL` in step 4 matches it exactly, with `https://`.
- [ ] Leave the Cloud edge HSTS toggle **off**. The app already sends the header
      with a one-year `max-age`; enabling it twice adds nothing and the header
      is very hard to walk back.

Check whether the free `*.laravel.cloud` URL sends `X-Robots-Tag: noindex`. On
2026-09-16 the `dev` URL did not, so add the header yourself if you want search
engines to index only the custom domain.

### Google OAuth for this environment

Local and Cloud use separate OAuth clients on purpose.

- [ ] In the Google Cloud console, create (or reuse) an OAuth client for this
      environment and add
      `https://your-domain.example/auth/google/callback` to its authorised
      redirect URIs. If you also want sign-in to work on the `*.laravel.cloud`
      URL, add that callback too.
- [ ] Put its id and secret in step 4, then redeploy.

---

## 8. Mail

`MAIL_MAILER=log` is the repository default. It writes email to the log file
instead of sending it. Password resets and booking notifications would look
fine and reach nobody.

- [ ] Choose a transport that `config/mail.php` already supports: `resend`,
      `postmark`, `ses`, or `smtp`. Set `MAIL_MAILER`, its credentials, and a
      real `MAIL_FROM_ADDRESS` in step 4.
- [ ] Send a password reset to yourself on the live site and confirm it arrives.

---

## 9. Content Security Policy rollout

The CSP ships in report-only mode. Browsers report violations and block nothing,
so it protects nothing until you enforce it.

- [ ] Deploy with `CSP_MODE=report`.
- [ ] Let real users click around for a few days. Your own clicks will not hit
      every page.
- [ ] Watch **Environment → Logs** for `CSP violation` entries.
- [ ] Widen `SecurityHeaders::contentSecurityPolicy()` only for legitimate
      sources.
- [ ] When the log is quiet, set `CSP_MODE=enforce` and redeploy.
- [ ] Re-check the map, both Filament panels, a pet photo upload, and a hotel
      photo upload in the admin panel (the second goes through Livewire).

Policy detail: `.claude/plans/plan-owasp-hardening.md`.

---

## 10. Go-live verification

Run from your machine, with the real domain:

```bash
# Generic error page, not a stack trace (expect 0)
curl -s https://YOUR_DOMAIN/nonexistent | grep -ci "stack trace"

# Security headers present
curl -sI https://YOUR_DOMAIN/ | grep -iE "strict-transport|x-frame|content-security"

# Session cookie is Secure, HttpOnly, SameSite
curl -sI https://YOUR_DOMAIN/ | grep -i "set-cookie: pet-hotel-session"
```

Then as a real user:

- [ ] Register, verify email, add a pet, upload a photo, and confirm the photo
      URL points at the bucket
- [ ] Book, then check the confirmation email arrives (this proves the worker
      and mail together)
- [ ] Cancel, and check the cancellation email arrives
- [ ] Google sign-in works on the custom domain
- [ ] `/admin` and `/owner` load, and only for admin and hotel-owner accounts
- [ ] Trigger a deliberate error and confirm no stack trace is shown

---

## 11. Known gaps

Not blockers, but you are accepting them:

- **No security event logging.** Failed logins, password resets, and admin
  actions are not recorded, so there is nothing to investigate after an incident.
- **`style-src 'unsafe-inline'`** is the weakest part of the CSP. Vue emits
  component styles inline; removing it needs nonces or hashes.
- **No payment integration.** Nothing to secure yet. That changes the moment it
  lands.
- **`is_admin` is a boolean, not roles.** Fine at current scale.
