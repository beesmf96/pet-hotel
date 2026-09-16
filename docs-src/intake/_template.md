---
title: {package or tool name}
description: {One sentence: what it is for.}
date: YYYY-MM-DD
status: accepted | removed
kind: composer | bun | binary | docker-image | github-action | php-extension
scope: runtime | dev | ci | docker
source: {URL of the repository}
license: {SPDX id}
version: {version pinned or installed}
---

# {package or tool name}

## Why we need it
What task needs it. What we would do without it, and why that is worse.

## Maintenance (checked YYYY-MM-DD)
- Last release: {date, version}
- Release cadence: {rough pattern over the last year}
- Maintainers: {count of active committers, org or individual}
- Issues: {open count and whether they get answered}

## Security (checked YYYY-MM-DD)
- Known CVEs or advisories: {list with fix version, or none found}
- Time to fix past advisories: {rough, or n/a}
- Transitive dependencies: {count, any red flag in the tree}
- Where checked: {osv.dev, GitHub advisories, packagist, npm}

## Alternatives
- {Option}: why not.

## Verdict
Accepted or rejected, and the one line reason. If later removed, add the date
and why here and set `status: removed` above.
