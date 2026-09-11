# CLAUDE.md

Guidance for Claude Code working in this repository.

**Pet Hotel** — a pet boarding marketplace. Stack, domain model, and the project-specific conventions and traps: `.claude/CLAUDE.md`. Feature roadmap: `docs/tasks.md` (Modules 0–9, all currently checked off).

## Commands

### Local (no Docker)
```bash
composer dev          # PHP server + queue worker + Pail + Vite, all at once
composer test         # Clears config cache, then PHPUnit
bun run dev           # Vite only
bun run test          # Vitest
bun run lint          # ESLint (--fix variant: bun run lint:fix)
vendor/bin/pint       # PHP formatter
```

### Documentation
```bash
php docs/build.php     # renders docs/*.md → docs/*.html and regenerates index.html
```

Markdown under `docs/` is the source of truth. Never hand-edit a generated
`.html` file — `user_guide.html`, `booking-flow.html`, and
`pet-hotel-boarding-mvp-v1.html` predate the renderer and are the only
hand-written pages left; they are listed in `STATIC_DOCS` in `docs/build.php`.
Page styling lives in `docs/assets/doc.css`.

### Docker
```bash
docker compose up -d                    # app, nginx, postgres, redis
docker compose --profile dev up -d      # + the Bun/Vite node container
docker compose exec --user appuser app <cmd>

# Backend coverage (pcov ships in the image, disabled unless you opt in)
docker compose exec --user appuser app \
  php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text
```

> **Always pass `--user appuser` to `docker compose exec`.** Compose v5 does not inherit the service's `user:` setting for exec, so without it commands run as root and leave root-owned files on the host that you cannot edit.
>
> To repair root-owned files: `docker compose exec -u root app chown -R 1000:1000 /var/www`

### Hosts file
Add to `/etc/hosts` (or `C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1  web.pet-hotel.local      # app — /admin and /owner serve the Filament panels
127.0.0.1  mailpit.local            # caught email
```

## Queue worker

Booking notifications (`app/Jobs/SendBooking*Notification.php`) are queued, and
they are the only thing that tells a customer their booking was requested,
confirmed, or cancelled. **Without a running worker, bookings still succeed and
no one is ever notified** — there is no error anywhere to tell you.

```bash
php artisan queue:work
```

Docker starts one as the `queue` service. Any hosted deployment needs it
configured as its own long-running process alongside the web process; on Laravel
Cloud that is a worker in the dashboard, not something the repo can declare.

## After any implementation task

1. Tests exist for the new behaviour, and `composer test` passes
2. `vendor/bin/pint`
3. For frontend changes, `bun run lint`

## CI

`.github/workflows/ci.yml` runs on every PR to, and push to, `main` or `dev`:

- **Backend** — `vendor/bin/pint --test`, then PHPUnit with pcov coverage
- **Security** — `composer audit` and `bun audit`
- **Frontend** — `bun run lint`, then `bun run test --run`

The backend job fails if line coverage drops below `MIN_COVERAGE` (currently `95`,
set at the top of the workflow). Raise that floor as coverage improves; don't lower
it to turn a build green. Reproduce the gate locally with:

```bash
docker compose exec --user appuser app \
  php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text
```

## Shipping work

Work happens in the main session; delegate to a subagent only when it genuinely helps. What holds regardless:

- Cut the `feature/{name}` branch first, before exploring or editing — never work directly on `main`
- Open a PR to `main` and leave it for human review — **do not merge**
- Code reads like the surrounding code. The non-obvious rules are in `.claude/CLAUDE.md`; everything else, match what is already there
- Verify before reporting: run the tests, run Pint, run the linter, and report the actual output — a failing test is reported as failing, not described as done

Plan files live in `.claude/plans/` as `plan-{name}.md`; start from `_template.md` and keep the frontmatter (`status`, `branch`, `pr`, `implemented`) current.
