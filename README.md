# Pet Hotel

A booking platform for pet boarding. Laravel 13 · Vue 3 · Inertia · Filament 4 · PostgreSQL · Redis.

## Run it locally

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

## More

- [Documentation](https://beesmf96.github.io/pet-hotel/) — user guide, booking flow, requirements, roadmap (stakeholder-facing, built from `notes/` into `docs/`); developer docs are the markdown under `notes/`
- [Deployment checklist](notes/deployment-checklist.md) — read before putting it on a public host
- [CLAUDE.md](CLAUDE.md) — conventions for working in the codebase
