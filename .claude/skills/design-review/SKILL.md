---
name: design-review
description: Design QA for the customer pages. Checks the Vue code against docs-src/design-system.md, screenshots every page and state at phone and desktop width, reviews them as a designer, asks before changing anything, then fixes approved items with tests and opens one PR to dev.
disable-model-invocation: true
---

# Design review

Run by the user after UI work, to keep the customer pages consistent.
**`docs-src/design-system.md` is the reference** — read it first, all of it.
A finding is a place where a page breaks that doc, or where two pages solve
the same thing in different ways. Taste alone is not a finding.

Argument, if given, narrows the scope: `code` (step 2 only), `visual`
(step 3 only), or a page name (`hotel`, `bookings`, `auth`, ...).

The Filament panels are out of scope.

## 1. Branch
Cut `feature/design-review-YYYY-MM-DD` from an up-to-date `dev`.

## 2. Code check
Scan `resources/js/Pages`, `resources/js/Components`, `resources/js/Layouts`
and `resources/css` (not `resources/js/tests`). Look for:
- hex values, `rgb(`, or shadows outside `tokens.css`; colours used against
  their role (doc §2)
- cards, buttons, fields, pills or notices built from classes instead of the
  `Ui` components and `card-hard` / `field-hard` / `link-bold` (doc §3, §6)
- corners, borders and shadows off the scales (doc §3)
- headings off the type scale; more than one `h1`; section headings not using
  `SectionTitle` (doc §4)
- page gutters set by a page; a content width that does not match its page
  kind (doc §5)
- `UiButton as="a"` or a plain `<a href>` to another page (doc §6)
- Title Case in headings or buttons; "..." instead of "…"; a submit button
  without a busy label (doc §8)
- prices, dates or nights not printed through `formatMoney()`,
  `formatDate()` or `nightsLabel()` (doc §8)
- anything still listed in doc §9

## 3. Visual walk
Needs the Docker stack up (`docker compose up -d`) and Chromium for
Playwright (`docs-src/local-setup.md`). Then:

```bash
bun run design:capture
```

This builds the assets, seeds `DesignReviewSeeder` into the throwaway e2e
database, and saves full-page screenshots to
`storage/design-review/{phone,desktop}/NN-name.png` (23 page states × 2
widths, about a minute). The shot list is `e2e/design/capture.spec.js` — when
a new page or state ships, add it there (and to the seeder if it needs data).

Read every screenshot. For each one, check against the doc:
- **Consistency across pages**: same kind of element, same look — headers,
  card padding, button order, back links, status pills, empty states.
  Compare pages side by side, not one at a time.
- **Hierarchy**: one clear main action; headings bigger than their content;
  quiet text in moss.
- **Phone width**: no sideways scroll, nothing cut off or overlapping, tap
  targets not cramped, nav still usable.
- **Words**: case, ellipsis, date and money formats match across pages.
- **Accessibility you can see**: text contrast on fills (teal needs cream
  text), focus rings on fields, labels on fields.

Use the `design:design-critique` and `design:accessibility-review` lenses if
they are available, but the doc wins where they disagree.

## 4. Report — then stop
One table. Each row: number, severity, what is wrong, where (screenshot name
and `file:line`), which doc rule, and the proposed fix.

| Severity | Meaning |
|---|---|
| **Fix** | Breaks a doc rule |
| **Align** | Two pages do the same thing differently; the doc does not decide yet. Propose a rule and the fix |
| **Note** | Worth knowing, no change proposed |

Mention the screenshot folder so the user can look too.

**Do not edit any code until the user approves.** The user answers per row,
or "all Fix", and so on. An approved **Align** item also updates
`docs-src/design-system.md` with the new rule, in the same PR.

## 5. Fix, one finding (or one kind of finding) at a time
1. If the change is visible in a test (text, a class a test reads, a
   `v-if` branch), update or add the Vitest spec first, per CLAUDE.md
   (`resources/js/tests/` mirrors `resources/js/`).
2. Make the change with the `Ui` components and tokens — never a new hex,
   shadow or inline class bundle.
3. Remove the item from doc §9 if it was listed there.
4. Commit it on its own commit.

## 6. Verify
Run and keep the real output:

```bash
bun run test
bun run lint
composer test        # in Docker: docker compose exec --user appuser app composer test
vendor/bin/pint      # if any PHP changed
bun run design:capture
```

Look at the new screenshots of every page you changed, at both widths, and
confirm each fix shows. A fix that is not visible in the screenshot is not done.

## 7. Paper trail and PR
- Session log entry in `docs-src/log/` per `docs-src/paper-trail.md`: the
  findings by severity, what was fixed, what was left and why, and any rule
  added to the design doc.
- Open a PR to `dev`. Leave it for review — do not merge.
- Report to the user: the PR link, findings by severity, and the real test,
  lint and Pint results.

If the review finds nothing to fix, say so and make no commit.
