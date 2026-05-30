# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Pet Hotel — a booking platform for pet boarding. Built with **Laravel 13 · Vue 3 · Inertia.js · Filament v4 · Sanctum · PostgreSQL · Redis**.

See `docs/tasks.md` for the full feature roadmap (Modules 0–9). Module 0 (infrastructure) is complete; Modules 1–9 are still being built.

## Key Commands

### Local development (no Docker)
```bash
composer dev          # Starts everything: PHP server, queue worker, Pail log viewer, Vite
composer test         # Clears config cache then runs PHPUnit
php artisan test --filter=TestName   # Run a single test
vendor/bin/pint       # Laravel Pint code formatter
bun run dev           # Frontend only (Vite dev server)
bun run build         # Production frontend build
```

### Docker
```bash
docker compose up -d                                              # Start app, nginx, postgres, redis
docker compose --profile dev up -d                               # Also start the Bun/Vite node container
docker compose exec --user appuser app php artisan migrate        # Run migrations inside container
docker compose exec --user appuser app php artisan <cmd>          # Run any artisan command (avoids finding php binary)
docker compose exec --user appuser app php artisan test           # Run tests in container
docker compose exec --user appuser app vendor/bin/pint            # Run Pint formatter in container
docker compose exec --user appuser app composer <cmd>             # Run composer in container
```

App is served at `http://web.pet-hotel.local` (nginx port 80) when running in Docker.
Admin panel is at `http://admin.pet-hotel.local`.
Mailpit web UI (email catcher) is at `http://mailpit.local`.

> **Host machine setup** — add to `/etc/hosts` (Linux/Mac) or `C:\Windows\System32\drivers\etc\hosts` (Windows):
> ```
> 127.0.0.1  web.pet-hotel.local
> 127.0.0.1  admin.pet-hotel.local
> 127.0.0.1  mailpit.local
> ```

> **Important:** Always pass `--user appuser` to `docker compose exec`. Docker Compose v5 does not inherit the service's `user:` setting for exec commands — without it, exec runs as root and creates root-owned files on the host that can't be edited directly.

> **One-time fix** (if files end up root-owned): `docker compose exec -u root app chown -R 1000:1000 /var/www`

### Artisan shortcuts
```bash
php artisan make:model ModelName -mfc        # Model + migration + factory + controller
php artisan make:filament-resource Name      # Filament admin resource
php artisan migrate:fresh --seed             # Wipe and re-seed DB
```

## Code Quality

### Post-implementation checklist

After finishing any implementation task:
1. Ensure tests exist for the new/changed behaviour — run `composer test` (local) or `docker compose exec --user appuser app php artisan test` (Docker)
2. Run Laravel Pint to fix PHP style — `vendor/bin/pint` (local) or `docker compose exec --user appuser app vendor/bin/pint` (Docker)
3. For frontend changes, run `bun run lint` (once ESLint is configured — see below)

### PHP — Laravel Pint

```bash
vendor/bin/pint                  # Auto-fix all PHP files
vendor/bin/pint --test           # Check only, no changes (useful in CI)
vendor/bin/pint app/Models/      # Fix a specific directory
```

Pint uses Laravel's default ruleset. No config file needed unless you want to customise rules.

### Frontend — ESLint + Prettier (setup required)

The project currently has no frontend linter. Recommended one-time setup for Vue 3 + Inertia:

```bash
# Install
bun add --dev eslint eslint-plugin-vue prettier eslint-config-prettier

# Then add to package.json scripts:
# "lint":   "eslint resources/js --ext .vue,.js --fix"
# "format": "prettier --write resources/js"
```

- `eslint-plugin-vue` — catches template errors, component naming, Vue-specific anti-patterns
- `prettier` — consistent formatting of `.vue` and `.js` files
- `eslint-config-prettier` — disables ESLint style rules that conflict with Prettier

Once configured, run `bun run lint` after any frontend change.

## Architecture

### Request flow
Browser → Nginx (`:80`, virtual hosts) → PHP-FPM (`app` container) → Laravel

Inertia.js bridges Laravel and Vue: Laravel returns `Inertia::render('PageName', $props)` instead of a Blade view, and the Vue SPA picks it up without a full page reload.

### Frontend structure
- Pages live in `resources/js/Pages/` — resolved by name from `app.js` via `resolvePageComponent`
- Shared layouts go in `resources/js/Layouts/` (e.g. `AppLayout.vue`)
- Global shared props (auth user, flash messages, etc.) are added in `app/Http/Middleware/HandleInertiaRequests.php` → `share()`
- Styling: Tailwind CSS v4 (via `@tailwindcss/vite` plugin — no `tailwind.config.js` needed)
- JS package manager: **Bun** (use `bun install`, not npm/pnpm)

### Backend structure
- `app/Models/` — Eloquent models
- `app/Http/Controllers/` — Inertia controllers (return `Inertia::render(...)`)
- `app/Filament/Resources/` — auto-discovered Filament admin resources
- `app/Filament/Pages/` — auto-discovered Filament pages
- `app/Filament/Widgets/` — auto-discovered Filament dashboard widgets
- Queue driver: Redis. Run `php artisan queue:work` or use `composer dev`

### Admin panel (Filament v4)
- Route: `http://admin.pet-hotel.local`, configured in `app/Providers/Filament/AdminPanelProvider.php`
- Served via its own nginx virtual host (`docker/nginx/conf.d/admin.conf`) with `->domain('admin.pet-hotel.local')->path('')`
- Auto-discovers Resources/Pages/Widgets from the `app/Filament/` directory
- Filament has its own auth stack (separate from Sanctum SPA auth)

### Authentication
- **Customer-facing**: Laravel Sanctum (cookie-based SPA auth), to be wired up in Module 1
- **Admin panel**: Filament's built-in auth at `http://admin.pet-hotel.local/login`

### Database
- PostgreSQL 16 (port `5432`). Credentials in `.env` / `.env.docker`
- Migrations in `database/migrations/`, seeders in `database/seeders/`
- A `database.sqlite` file exists for quick local runs without Docker (change `DB_CONNECTION=sqlite` in `.env`)

## Skill routing

When the user's request matches an available skill, invoke it via the Skill tool. When in doubt, invoke the skill.

Key routing rules:
- Product ideas/brainstorming → invoke /office-hours
- Strategy/scope → invoke /plan-ceo-review
- Architecture → invoke /plan-eng-review
- Design system/plan review → invoke /design-consultation or /plan-design-review
- Full review pipeline → invoke /autoplan
- Bugs/errors → invoke /investigate
- QA/testing site behavior → invoke /qa or /qa-only
- Code review/diff check → invoke /review
- Visual polish → invoke /design-review
- Ship/deploy/PR → invoke /ship or /land-and-deploy
- Save progress → invoke /context-save
- Resume context → invoke /context-restore
