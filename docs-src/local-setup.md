---
title: Local Setup
description: Running Pet Hotel on your own machine with Docker, plus the everyday commands and the one-time commit hook.
badges: Operations
order: 45
---

# Local Setup

A booking platform for pet boarding. Laravel 13 · Vue 3 · Inertia · Filament 5 · PostgreSQL · Redis.

## Run it

You need Docker and Git.

```bash
cp .env.docker .env
docker compose up -d
docker compose exec --user appuser app php artisan key:generate
docker compose exec --user appuser app php artisan migrate --seed
```

Add to your hosts file (`/etc/hosts`, or `C:\Windows\System32\drivers\etc\hosts` on Windows):

```
127.0.0.1  web.pet-hotel.local
127.0.0.1  mailpit.local
```

Then open:

| URL | What |
|---|---|
| http://web.pet-hotel.local | The app |
| http://web.pet-hotel.local/admin | Admin panel — `admin@pethotel.test` / `password` |
| http://web.pet-hotel.local/owner | Hotel owner panel — `owner@example.com` / `password` |
| http://mailpit.local | Caught email |

The seed also creates the people in [Personas](personas.md) — `aisyah@example.com`,
`daniel@example.com`, `meiling@example.com` and the owner `rahman@example.com`, all with
password `password` — with bookings around the next 1–5 November.

Frontend hot reload: `docker compose --profile dev up -d` starts the Vite container.

## Everyday commands

```bash
docker compose exec --user appuser app php artisan test    # PHP tests
docker compose exec --user appuser app vendor/bin/pint     # PHP formatter
bun run test                                               # JS tests
bun run lint                                               # ESLint
docker compose down                                        # stop (keeps data); add -v to wipe it
```

Always pass `--user appuser` to `docker compose exec`, or files created inside the container end up owned by root on your machine.

Once per clone, turn on the secret-scanning commit hook:

```bash
git config core.hooksPath .githooks
```

It runs gitleaks on staged changes and refuses the commit if something looks like a credential.

## End-to-end tests (local only)

`bun run e2e` clicks through the booking flow in a real browser with
Playwright: owner settings and availability, the calendar and form, requests
and confirm, the cancel and decline emails, capacity, prices, and the two
notification bells. It is not part of CI.

It needs the Docker stack up (`docker compose up -d`). Once per machine,
download the browser:

```bash
bunx playwright install chromium
# On WSL or a bare Linux, if Chromium will not start, add its system libraries:
sudo bunx playwright install-deps chromium
```

Then:

```bash
bun run e2e                          # the whole suite, about 2 minutes
bun run e2e e2e/c-requests-and-confirm.spec.js   # one file
```

It starts its own copy of the app on http://localhost:8001 in a throwaway
container (`pet_hotel_e2e`), with its own SQLite database
(`database/e2e.sqlite`), mail sent at once, and built assets. Your dev
database and dev mail are not touched: each spec resets the e2e database
after checking it really is the e2e one, and only mail to `@e2e.test`
addresses is read or deleted in Mailpit. Fixed test data comes from
`E2eSeeder` (password `password` for every account). Failures leave a
screenshot, a trace and a page snapshot under `storage/e2e-results/`, and an
HTML report in `storage/e2e-report/`.

### Design screenshots

`bun run design:capture` uses the same throwaway server to screenshot every
customer page and state, at phone and desktop width, into
`storage/design-review/`. It seeds `DesignReviewSeeder` (the personas plus
reviews, more stays, notifications and an empty account). The `/design-review`
skill runs it; the shot list is `e2e/design/capture.spec.js`.

## More

- [Documentation](https://beesmf96.github.io/pet-hotel/) — the stakeholder site, built from `docs-src/` into `docs/`
- [Deployment checklist](deployment-checklist.md) — read before putting it on a public host
- [CLAUDE.md](../CLAUDE.md) — conventions for working in the codebase
