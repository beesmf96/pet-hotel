# Pet Hotel

A booking platform for pet boarding. Built with Laravel 13 · Vue 3 · Inertia.js · Filament v4 · PostgreSQL · Redis.

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose v2
- Git

---

## Local Development Setup

### 1. Clone the repository

```bash
git clone <repo-url> pet-hotel
cd pet-hotel
```

### 2. Set up environment file

```bash
cp .env.docker .env
```

The `.env.docker` file is pre-configured for Docker (PostgreSQL host `postgres`, Redis host `redis`, app URL `http://web.pet-hotel.local`, Mailpit for email).

### 3. Add local hostnames

Add these entries to your hosts file — `/etc/hosts` on Linux/Mac, `C:\Windows\System32\drivers\etc\hosts` on Windows:

```
127.0.0.1  web.pet-hotel.local
127.0.0.1  admin.pet-hotel.local
127.0.0.1  owner.pet-hotel.local
127.0.0.1  mailpit.local
```

### 4. Build and start the containers

```bash
docker compose up -d
```

This starts six services:

| Container             | Description              | Host port |
|-----------------------|--------------------------|-----------|
| `pet_hotel_app`       | PHP-FPM (Laravel)        | —         |
| `pet_hotel_nginx`     | Nginx web server         | 80        |
| `pet_hotel_postgres`  | PostgreSQL 16            | 5432      |
| `pet_hotel_redis`     | Redis 7                  | 6379      |
| `pet_hotel_queue`     | Laravel queue worker     | —         |
| `pet_hotel_mailpit`   | Mailpit email catcher    | —         |

### 5. Generate the application key

```bash
docker compose exec --user appuser app php artisan key:generate
```

### 6. Run migrations and seed the database

```bash
docker compose exec --user appuser app php artisan migrate --seed
```

### 7. Open the app

- **App**: http://web.pet-hotel.local
- **Admin panel**: http://admin.pet-hotel.local
- **Owner portal**: http://owner.pet-hotel.local
- **Mailpit** (email UI): http://mailpit.local

---

## Frontend Hot Reload (Vite)

For active frontend development, start the `node` container (Bun + Vite dev server):

```bash
docker compose --profile dev up -d
```

Vite will be available at http://localhost:5173. The app served at `web.pet-hotel.local` will automatically use the hot-reload assets.

---

## Daily Workflow

### Start / stop

```bash
docker compose up -d          # Start all services
docker compose down           # Stop and remove containers (data volumes are preserved)
docker compose down -v        # Stop and also wipe all data volumes
```

### Run artisan commands

```bash
docker compose exec --user appuser app php artisan <command>
```

### Run tests

```bash
docker compose exec --user appuser app php artisan test
```

### Fix PHP code style (Laravel Pint)

```bash
docker compose exec --user appuser app vendor/bin/pint
```

### Lint / format frontend

```bash
bun run lint        # Report ESLint violations
bun run lint:fix    # Auto-fix ESLint violations
bun run format      # Prettier format
```

### Wipe and re-seed the database

```bash
docker compose exec --user appuser app php artisan migrate:fresh --seed
```

---

## Project Structure

```
app/
  Http/Controllers/     # Inertia controllers
  Models/               # Eloquent models
  Filament/             # Admin panel resources, pages, widgets
resources/js/
  Pages/                # Vue page components (resolved by Inertia)
  Layouts/              # Shared Vue layouts
docker/
  nginx/                # Nginx config
  php/                  # Dockerfile + php.ini overrides
docs/
  tasks.md              # Feature roadmap (Modules 0–9)
```

---

## Troubleshooting

**App key not set** — Run step 4 (`key:generate`). The app will throw a 500 without it.

**Database connection refused on first boot** — PostgreSQL takes a few seconds to initialize. The `app` container waits for a health check, but if you hit an error immediately after `up -d`, wait 10 seconds and retry.

**Port conflicts** — If `:80`, `:5432`, or `:6379` are in use on your machine, edit the `ports` mappings in `docker-compose.yml` before starting.
