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

## AI Review Agents

We use Claude Code agents for PR review. Available agents:

- `@agent-pr-reviewer` — full pre-merge review (run before opening PR)
- `@agent-security-reviewer` — security spot check
- `@agent-code-quality` — architecture review
- `@agent-test-coverage` — test gap analysis
- `@agent-secrets-scanner` — credential scan
- `@agent-dependency-auditor` — package CVE check

Recommended: run `@agent-pr-reviewer` before every merge.

<!-- rtk-instructions v2 -->
# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash
# ❌ Wrong
git add . && git commit -m "msg" && git push

# ✅ Correct
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)
```bash
rtk cargo build         # Cargo build output
rtk cargo check         # Cargo check output
rtk cargo clippy        # Clippy warnings grouped by file (80%)
rtk tsc                 # TypeScript errors grouped by file/code (83%)
rtk lint                # ESLint/Biome violations grouped (84%)
rtk prettier --check    # Files needing format only (70%)
rtk next build          # Next.js build with route metrics (87%)
```

### Test (60-99% savings)
```bash
rtk cargo test          # Cargo test failures only (90%)
rtk go test             # Go test failures only (90%)
rtk jest                # Jest failures only (99.5%)
rtk vitest              # Vitest failures only (99.5%)
rtk playwright test     # Playwright failures only (94%)
rtk pytest              # Python test failures only (90%)
rtk rake test           # Ruby test failures only (90%)
rtk rspec               # RSpec test failures only (60%)
rtk test <cmd>          # Generic test wrapper - failures only
```

### Git (59-80% savings)
```bash
rtk git status          # Compact status
rtk git log             # Compact log (works with all git flags)
rtk git diff            # Compact diff (80%)
rtk git show            # Compact show (80%)
rtk git add             # Ultra-compact confirmations (59%)
rtk git commit          # Ultra-compact confirmations (59%)
rtk git push            # Ultra-compact confirmations
rtk git pull            # Ultra-compact confirmations
rtk git branch          # Compact branch list
rtk git fetch           # Compact fetch
rtk git stash           # Compact stash
rtk git worktree        # Compact worktree
```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)
```bash
rtk gh pr view <num>    # Compact PR view (87%)
rtk gh pr checks        # Compact PR checks (79%)
rtk gh run list         # Compact workflow runs (82%)
rtk gh issue list       # Compact issue list (80%)
rtk gh api              # Compact API responses (26%)
```

### JavaScript/TypeScript Tooling (70-90% savings)
```bash
rtk pnpm list           # Compact dependency tree (70%)
rtk pnpm outdated       # Compact outdated packages (80%)
rtk pnpm install        # Compact install output (90%)
rtk npm run <script>    # Compact npm script output
rtk npx <cmd>           # Compact npx command output
rtk prisma              # Prisma without ASCII art (88%)
```

### Files & Search (60-75% savings)
```bash
rtk ls <path>           # Tree format, compact (65%)
rtk read <file>         # Code reading with filtering (60%)
rtk grep <pattern>      # Search grouped by file (75%). Format flags (-c, -l, -L, -o, -Z) run raw.
rtk find <pattern>      # Find grouped by directory (70%)
```

### Analysis & Debug (70-90% savings)
```bash
rtk err <cmd>           # Filter errors only from any command
rtk log <file>          # Deduplicated logs with counts
rtk json <file>         # JSON structure without values
rtk deps                # Dependency overview
rtk env                 # Environment variables compact
rtk summary <cmd>       # Smart summary of command output
rtk diff                # Ultra-compact diffs
```

### Infrastructure (85% savings)
```bash
rtk docker ps           # Compact container list
rtk docker images       # Compact image list
rtk docker logs <c>     # Deduplicated logs
rtk kubectl get         # Compact resource list
rtk kubectl logs        # Deduplicated pod logs
```

### Network (65-70% savings)
```bash
rtk curl <url>          # Compact HTTP responses (70%)
rtk wget <url>          # Compact download output (65%)
```

### Meta Commands
```bash
rtk gain                # View token savings statistics
rtk gain --history      # View command history with savings
rtk discover            # Analyze Claude Code sessions for missed RTK usage
rtk proxy <cmd>         # Run command without filtering (for debugging)
rtk init                # Add RTK instructions to CLAUDE.md
rtk init --global       # Add RTK to ~/.claude/CLAUDE.md
```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->

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
