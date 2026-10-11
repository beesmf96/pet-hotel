---
title: php artisan serve drops most environment variables, so the served app re-reads .env
description: Overrides passed as environment variables (DB, queue, cache) do not reach an app started with artisan serve, which then talks to the database in .env.
date: 2026-10-11
tags: laravel, artisan, environment, e2e, database
---

# php artisan serve drops most environment variables

## What we learnt
`php artisan serve` starts PHP's built-in server as a child process. When a
`.env` file exists, it passes the child only the names in
`ServeCommand::$passthroughVariables` (`APP_ENV`, `PATH`, the Xdebug and Herd
ones, …) and unsets everything else, so the child loads `.env` afresh. A
`DB_CONNECTION=sqlite` set on the command line is therefore ignored by the
served app, which uses the `.env` database. `--no-reload` keeps the whole
environment; so does running `php -S … vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`
directly. (`APP_ENV` does pass through, and with it Laravel also loads a
`.env.{APP_ENV}` file if one exists.)

## How we found out
On 2026-10-11 the first e2e server, started with `artisan serve` and
SQLite overrides, still served Vite dev-server URLs. Reading
`vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php`
(`startProcess()`) showed the filtering. Had the e2e suite run, it would have
written to the dev database.

## How to apply
- To serve the app with environment overrides, use `php -S` with Laravel's
  `server.php` (what `playwright.config.js` does) or `artisan serve --no-reload`.
- After starting such a server, check it is using the intended database
  before any test writes to it.
- Log entry: `docs-src/log/2026-10-11-e2e-playwright.md`.
