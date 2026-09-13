---
title: gitleaks
description: Secret scanner run by the pre-commit hook, as a local binary or the official Docker image.
date: 2026-09-13
status: accepted
kind: binary
scope: dev
source: https://github.com/gitleaks/gitleaks
license: MIT
version: v8.30.1 (Docker image zricethezav/gitleaks:v8.30.1; local binary unpinned)
---

# gitleaks

Written after the fact. gitleaks was added in PR #32 on 2026-09-12, before the
intake rule existed. This is the first intake entry and records the check as
of today.

## Why we need it
The pre-commit hook in `.githooks/pre-commit` scans staged changes for
anything that looks like a credential and refuses the commit. GitHub push
protection covers known provider token formats, but only after the push.
gitleaks catches generic patterns (private keys, high-entropy strings) and
catches them before a commit exists, so nothing has to be rewritten out of
history.

Without it: a hand-written grep hook for a few key formats. That misses the
entropy check and needs its own maintenance every time a provider changes a
token format. The ruleset upstream is the value.

The hook runs the local binary if present, otherwise the pinned Docker image.
Both use the shared `.gitleaks.toml`.

## Maintenance (checked 2026-09-13)
- Last release: v8.30.1, 2026-03-21. Last push to the repo: 2026-09-09.
- Release cadence: 12 releases in the twelve months to March 2026, then a six
  month gap with commits but no release. Slower than before, not stalled.
- Maintainers: two people account for most commits in the last year (23 of
  about 40 non-bot commits). A handful of others contribute. Backed by
  Gitleaks LLC, which sells the Action licence. Bus factor is low but there is
  a company behind it.
- Issues: about 270 open issues, 15 closed in the last six months. Issues
  pile up faster than they close. Active repo, 29k stars, not archived.

## Security (checked 2026-09-13)
- Known CVEs or advisories: none found for the Go module under either the
  `gitleaks/gitleaks` or older `zricethezav/gitleaks` path.
- Time to fix past advisories: n/a, none on record.
- Transitive dependencies: Go module with 72 requirements, 56 of them
  indirect. Dependabot is active in the repo. Nothing flagged in the tree.
- Where checked: osv.dev API, GitHub Advisory Database, GitHub releases.
- Trust surface: the hook reads the whole staged diff. The Docker path mounts
  the repo read-write into the container. The image is pinned by tag, not by
  digest, so a re-tag upstream would change what runs.

## Alternatives
- trufflehog (Truffle Security): stronger on verifying live credentials, but
  heavier and its verification makes outbound calls from a pre-commit hook.
- detect-secrets (Yelp): Python, baseline-file workflow, slower release pace.
- GitHub push protection alone: post-push only, provider tokens only.

## Verdict
Accepted. Dev-only scope, permissive licence, no advisories, active upstream
with a company behind it. Two follow-ups worth doing, neither blocking: pin
the Docker image by digest, and pin a minimum version for the local binary so
both paths run the same rules.
