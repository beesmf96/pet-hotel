---
title: The gitleaks pre-commit hook scans nothing inside a git worktree
description: The Docker fallback mounts the worktree but its .git file points outside the mount, so the scan errors and the commit goes through unscanned.
date: 2026-09-13
tags: git, gitleaks, hooks, worktree, docker
---

# The gitleaks pre-commit hook scans nothing inside a git worktree

## What we learnt
`.githooks/pre-commit` falls back to the `zricethezav/gitleaks` Docker image
when no local `gitleaks` binary exists. It mounts `git rev-parse
--show-toplevel` as `/repo`. In a worktree created with `git worktree add`,
`.git` is a file that points to `<main checkout>/.git/worktrees/<name>`, which
is outside the mount. gitleaks then fails with `not a git repository` and
`stderr is not empty`, reports `0 bytes scanned`, and **exits 0**. The commit
is created with no scan at all. Nothing in the output says "blocked", so it
is easy to miss.

The hook works as intended in the main checkout, and would work in a
worktree if a local `gitleaks` binary is installed, because the binary reads
the `.git` file natively.

## How we found out
Committing a one-line change from a scratch worktree while another session
held the main checkout on a different branch (PR #36, 2026-09-13). The
hook printed two `ERR` lines and then `no leaks found`.

## How to apply
- Do not trust a green hook result from a worktree unless a local binary is
  installed. `which gitleaks` tells you which path the hook took.
- CI still scans the pushed range, so a missed local scan is caught at PR
  time. It is a gap in the early warning, not in the gate.
- Fix candidate: mount the main repo's `.git` directory too, or resolve the
  worktree's real git dir (`git rev-parse --git-common-dir`) and mount it at
  the same path inside the container. Until then, install the binary on any
  machine that uses worktrees.
