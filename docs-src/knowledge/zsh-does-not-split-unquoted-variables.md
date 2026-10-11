---
title: zsh does not word-split an unquoted variable, so docker flags kept in one variable arrive as one argument
description: A list of `-e KEY=value` flags stored in a shell variable reached Docker as a single garbled variable, and a destructive migrate:fresh ran against the dev database instead of the e2e one.
date: 2026-10-11
tags: shell, zsh, docker, database, safety
---

# zsh does not word-split an unquoted variable

## What we learnt
bash splits an unquoted `$E` on spaces; zsh does not. In zsh,
`E="-e DB_CONNECTION=sqlite -e DB_DATABASE=…"; docker compose run $E app …`
passes `$E` as **one** argument, so Docker sets a single variable (here
`APP_ENV="e2e -e APP_URL=… -e DB_CONNECTION=sqlite …"`) and every other
override is silently dropped. Laravel then falls back to `.env` — the dev
PostgreSQL database. The user's shell, and the shell Claude Code's Bash tool
runs, is zsh.

## How we found out
While setting up the Playwright suite on 2026-10-11, the command meant to
reset the e2e SQLite file (`migrate:fresh --seeder=E2eSeeder --force`) wiped
and reseeded the dev database instead. A later `tinker` check showed the dev
database holding only the e2e hotel; the same command with the flags written
out in full used SQLite as intended.

## How to apply
- Never build a list of command-line flags in a shell variable. Write the
  flags out, use a zsh array (`flags=(-e A=1 -e B=2); cmd "${flags[@]}"`), or
  start the process from code with an argument list (as
  `playwright.config.js` does).
- Before any destructive artisan command (`migrate:fresh`, `db:wipe`,
  `migrate:rollback`) against a database other than the default, first print
  what Laravel resolved —
  `php artisan tinker --execute='echo config("database.default"), " ", DB::connection()->getDatabaseName();'`
  — with the same flags, and only then run it.
- Seeders meant for one environment should refuse to run anywhere else
  (`E2eSeeder` checks `app()->environment('e2e')`).
- Log entry: `docs-src/log/2026-10-11-e2e-playwright.md`.
