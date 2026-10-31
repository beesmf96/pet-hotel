---
title: Minor dependency updates
description: Patch and minor updates for PHP and JS packages, which clear 10 of the 12 dev-tool advisories.
date: 2026-10-31
pr: 89
plan: ~
---

# Minor dependency updates

## Asked
After a dependency check, do the minor updates as the second step, after the
`symfony/mime` fix (PR #88), "then run the test ensure it pass".

## Done
- PHP: `laravel/framework` 13.39, `inertiajs/inertia-laravel` 3.6,
  `laravel/socialite` 5.30, `laravel/pint`, `laravel/pao`, and their
  dependencies within the same major version.
- JS: `vue`, `@inertiajs/vue3`, `vite`, `vitest`, `@vitest/coverage-v8`,
  `eslint`, `eslint-plugin-vue`, `prettier`, `@vitejs/plugin-vue`. `undici`
  moved to 7.30.1 through `jsdom`.
- `bun audit` drops from 12 advisories (4 high) to 2 moderate.

## Not done
- **Guzzle and phpseclib stay on their current major versions.** A plain
  `composer update -W` moved `guzzlehttp/guzzle` to 8, `promises` to 3, `psr7`
  to 3 and `phpseclib` to 4. Google login and the S3 uploads run on these, and
  neither is exercised for real by the tests, so the update was redone with
  `--with 'guzzlehttp/guzzle:^7'` and the same for the other three. That is
  also why `laravel/socialite` stops at 5.30 and not 5.31. Move these in their
  own PR.
- **`glob` and `minimatch` advisories remain.** The fixes need `glob` above
  14.0.1 and `minimatch` 11.0.1, outside what `eslint` and `js-beautify` allow.
  Both are dev tools only; `bun audit --prod` is clean.
- Major updates (Filament 5, PHPUnit 13, Vitest 5, jsdom 30, concurrently 10)
  and `@playwright/test`, which is pinned.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `composer test`: 551 passed. Coverage could not be measured in the local
  container (no pcov); CI measures it.
- `vendor/bin/pint --test`: pass. `bun run test`: 342 passed.
  `bun run lint`: 0 errors, the same 5 old warnings. `bun run build`: pass.
- `composer audit`: no advisories. `bun audit --prod`: no vulnerabilities.
- `bun run design:capture`: 8 passed, so customer pages load on the new Inertia
  versions.
- Not checked: a real Google login and a real bucket upload.
