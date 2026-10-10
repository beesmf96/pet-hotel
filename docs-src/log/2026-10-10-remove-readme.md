---
title: README removed, setup guide moved into docs-src
description: The repo no longer has a README; its local setup steps, everyday commands and commit hook note now live in docs-src/local-setup.md.
date: 2026-10-10
pr: ~
plan: ~
---

# README removed, setup guide moved into docs-src

## Asked
"I feel like I don't want to show the README in GitHub, probably can remove
the README file." Of the options offered (short README, private repo, remove
it fully), the user chose to remove it fully.

## Done
- Docs: `README.md` is deleted. Its content moved to `docs-src/local-setup.md`
  (coder-facing, not rendered to `docs/`): Docker setup, hosts lines, seeded
  panel logins, everyday commands, and the one-time
  `git config core.hooksPath .githooks` step for gitleaks. Its links were
  made relative to `docs-src/`.
- `CLAUDE.md` points to the new file under Commands, as nothing else did.
- `.gitignore`: the `.env.docker` comment names the new file.

## Not done
- The old README stays in git history, and the repo is still public; this
  changes only what GitHub shows on the front page.
- Past log entries and plans that mention the README were left as written.

## Produced
- ADR: none
- Knowledge: none
- Intake: none

## Verified
- `php docs-src/build.php` renders the same five docs; `docs/` is unchanged.
- No code changed, so tests, Pint and ESLint were not run.
