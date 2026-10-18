---
name: dry-sweep
description: Periodic sweep for repeated code in backend and frontend. Finds copies that break the rules in rules.md, asks before changing anything, extracts approved ones with tests, and opens one PR to dev.
disable-model-invocation: true
---

# DRY sweep

Run by the user from time to time to keep repeated code under control.
Read `rules.md` in this folder first — it decides what counts. Argument, if
given, narrows the scope (for example `backend`, `frontend`, or a path).

## 1. Branch
Cut `feature/dry-sweep-YYYY-MM-DD` from an up-to-date `dev`. Never work on
`dev` or `main`.

## 2. Scan
Look in:
- `app/` (skip nothing but what rules.md §4 excludes)
- `resources/js/` (not `resources/js/tests/`)
- `resources/css/`

Never scan `database/migrations/`, `config/`, `vendor/`, `node_modules/`,
`docs/`, `public/build/`.

Ways to find candidates (use what fits; no new packages):
- Repeated literals: format strings (`->format('...')`, `toLocaleDateString`),
  magic numbers, status strings, route names, error messages.
- Repeated query shapes: the same `where`/`with` chain, owner scoping.
- Repeated Tailwind class bundles on buttons, fields, cards, pills, notices
  that should use a `Components/Ui/` component or `card-hard`/`field-hard`.
- Repeated hex values or shadows outside `tokens.css`.
- Functions or Vue blocks with the same body under different names.
- Code that rebuilds something a shared home already does
  (`Money`, `Availability`, `Stay`, `PhotoUrl`, `money.js`, `stay.js`,
  `petTypes.js`, `useFormatDate`, the `Ui` components). This counts on the
  first copy — the helper already exists.

Skip any copy that carries a `dry-keep:` comment.

## 3. Classify and report — then stop
Put each finding in one of four groups:

| Group | Meaning |
|---|---|
| **Extract** | Breaks rule 1 or 3, or rebuilds an existing helper |
| **Leave** | Only looks alike (rule 2), or only 2 copies of non-business code |
| **Ask** | Looks deliberate but has no `dry-keep:` marker, or you are unsure |
| **Inline back** | A shared helper that grew a flag per caller (rule 7) |

Show the user a table: group, what repeats, count, file:line of each copy,
and the proposed home (rules.md §6) with a proposed name. For **Ask** items,
say why it might be deliberate.

**Do not edit any code until the user approves.** For each Ask item the user
answers either "extract" or "keep" — on "keep", add a `dry-keep:` comment with
their reason at every copy.

## 4. Change, one finding at a time
For each approved finding:
1. **Pin behaviour first.** Find the tests that cover every copy. If a copy
   has no test, write one against the current behaviour and run it green
   before changing anything.
2. **Extract** to the agreed home. Update every caller. No behaviour change —
   the output must stay byte for byte the same.
3. **Test the new home directly.** PHP helper → `tests/Unit/Support/` (or
   `tests/Unit/Models/` for a model method). JS helper → Vitest spec in
   `resources/js/tests/` mirroring its path. A new `Ui` component → a Vitest
   spec, one test per prop-driven `v-if` branch.
4. Run the relevant tests, then commit this finding on its own commit.

## 5. Verify everything
Run all of these and keep the real output:

```bash
composer test
vendor/bin/pint
bun run test
bun run lint
```

All must pass. Coverage must not drop below `MIN_COVERAGE` in
`.github/workflows/ci.yml`. If anything fails, fix it — do not open the PR
with a red check.

## 6. Paper trail and PR
- Session log entry in `docs-src/log/` per `docs-src/paper-trail.md`: list
  what was extracted, what was marked `dry-keep:` and why, and what was left.
- Open a PR to `dev`. Leave it for review — do not merge.
- Report to the user: the PR link, findings by group, and the real test,
  Pint and lint results.

If the sweep finds nothing to extract, say so, make no branch commit, and
write no log entry.
