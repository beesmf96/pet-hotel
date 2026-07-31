# CLAUDE.md

Guidance for Claude Code working in this repository.

**Pet Hotel** — a pet boarding marketplace. Stack and domain model: `.claude/CLAUDE.md`. Code conventions: `.claude/agents/coder.md`, `tester.md`, `linter.md`. Feature roadmap: `docs/tasks.md` (Modules 0–9, all currently checked off).

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
127.0.0.1  web.pet-hotel.local      # app
127.0.0.1  admin.pet-hotel.local    # Filament admin panel
127.0.0.1  owner.pet-hotel.local    # Filament hotel-owner panel
127.0.0.1  mailpit.local            # caught email
```

## After any implementation task

1. Tests exist for the new behaviour, and `composer test` passes
2. `vendor/bin/pint`
3. For frontend changes, `bun run lint`

## Shipping work

How you get there is your call — subagents, straight-through, whatever fits the task. What holds regardless:

- Work on a `feature/{name}` branch, never directly on `main`
- Open a PR to `main` and leave it for human review — **do not merge**
- `.claude/agents/coder.md`, `tester.md`, and `linter.md` define this codebase's conventions. Consult them when writing or reviewing code, whether or not you delegate to an agent.

Plan files live in `.claude/plans/` as `plan-{name}.md`; start from `_template.md` and keep the frontmatter (`status`, `branch`, `pr`, `implemented`) current.
