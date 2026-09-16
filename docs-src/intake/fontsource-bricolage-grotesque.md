---
title: "@fontsource-variable/bricolage-grotesque"
description: Self-hosted variable webfont for the landing page display type.
date: 2026-09-16
status: accepted
kind: bun
scope: runtime
source: https://github.com/fontsource/fontsource
license: OFL-1.1
version: 5.3.0
---

# @fontsource-variable/bricolage-grotesque

## Why we need it
The redesigned landing page uses Bricolage Grotesque for headlines. The CSP
allows fonts only from `'self'` and `data:`, so a Google Fonts `<link>` would
be blocked. This package ships the font files through Vite, so they are served
from our own origin with no CSP change and no third-party request on page load.
Without it the headline falls back to the system sans, which loses the
character the design was chosen for.

## Maintenance (checked 2026-09-16)
- Last release: 2026-07-19, 5.3.0
- Release cadence: rebuilt whenever the upstream Google Fonts source updates
- Maintainers: fontsource org, several active committers
- Issues: monorepo tracker, actively answered

## Security (checked 2026-09-16)
- Known CVEs or advisories: none found
- Time to fix past advisories: n/a
- Transitive dependencies: 0
- Where checked: npm, GitHub advisories

## Alternatives
- Google Fonts `<link>`: needs `fonts.googleapis.com` in `style-src` and
  `fonts.gstatic.com` in `font-src`, and adds a third-party request.
- Copy the `.woff2` into `public/fonts/`: works, but no version tracking or
  audit coverage.
- System font stack: no dependency, but the headline loses its character.

## Verdict
Accepted. Self-hosted, zero dependencies, OFL, keeps the CSP unchanged.
