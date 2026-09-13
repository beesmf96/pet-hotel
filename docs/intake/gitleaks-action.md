---
title: gitleaks-action
description: GitHub Action that runs gitleaks over the PR commit range in the CI security job.
date: 2026-09-13
status: accepted
kind: github-action
scope: ci
source: https://github.com/gitleaks/gitleaks-action
license: Proprietary (Gitleaks LLC EULA, source-available; MIT before v2.0.0)
version: v2 (floating major tag)
---

# gitleaks-action

Written after the fact. Added in PR #32 on 2026-09-12. Separate from the
`gitleaks` entry because the licence and the maintenance picture are
different.

## Why we need it
Runs the same scan as the local hook, in CI, over the full commit range of a
push or PR. The hook is opt-in per clone (`git config core.hooksPath`), so
CI is the only path that cannot be skipped.

Without it: a plain `run:` step that downloads the gitleaks binary and calls
`gitleaks git` on the range. About ten lines. The Action adds PR comments and
a job summary, both switched off in our workflow. The Action is convenience,
not capability.

## Maintenance (checked 2026-09-13)
- Last release: v3.0.0, 2026-05-30. Last push: 2026-07-21.
- Release cadence: one or two releases a year. Eight commits in the last
  twelve months.
- Maintainers: same people as gitleaks, via Gitleaks LLC.
- Issues: 63 open. Low activity; the Action is a thin wrapper.

## Security (checked 2026-09-13)
- Known CVEs or advisories: none found.
- Transitive dependencies: Node action bundled into `dist/`. Runtime deps are
  `@actions/core` and the gitleaks binary it downloads at run time.
- Where checked: GitHub Advisory Database, GitHub releases.
- **Deadline.** `v2` runs on Node 20. GitHub removes Node 20 from hosted
  runners on **2026-09-16**, after which `gitleaks-action@v2` fails on every
  run. `v3` is a runtime bump only, no input or behaviour change. The CI
  workflow must move to `gitleaks/gitleaks-action@v3` before then. Upstream
  also recommends `actions/checkout@v6` alongside it; the workflow is on v5.
- Licence. From v2.0.0 the Action is under a Gitleaks LLC end-user licence,
  not MIT. Personal accounts need no key. Organization accounts need a free
  licence key set as `GITLEAKS_LICENSE`. The Action enforces this itself.
  This repo is on a personal account, so no key today. If the repo moves to
  an organization, CI breaks until a key is added.

## Alternatives
- A `run:` step with the binary: keeps everything under MIT, no licence key
  question, same rules. Ten lines to maintain ourselves.
- trufflehog-actions-scan: different scanner, see the `gitleaks` entry.

## Verdict
Accepted, with two conditions. Bump to `v3` before 2026-09-16 or CI stops.
If the repo ever moves to an organization account, either add the free
licence key or replace the Action with the `run:` step above; the licence
change is the reason the plain step is the fallback.
