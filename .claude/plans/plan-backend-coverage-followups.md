---
plan: backend-coverage-followups
status: in-progress
branch: feature/photos-relation-manager-coverage
pr: https://github.com/beesmf96/pet-hotel/pull/9
implemented: 2026-08-01 (item 3 only; items 1 and 2 deferred to the maintainer)
---

# Backend Coverage — Follow-ups

## What & Why

PR #8 (merged 2026-08-01, commit `3309816`) took backend line coverage from 54.64%
to 98.36%, fixed five date-rotted tests, fixed three admin-panel bugs, and added
`.github/workflows/ci.yml` with a 95% coverage gate.

Three things were deliberately left out of that PR. This file tracks them so they
don't get lost. None of them block anything; all three are independent and can be
picked up in any order.

## Out of Scope

Anything that was already delivered in PR #8. For the record, that PR fixed:

- `HotelResource` — Filament v3 `Forms\Set` type-hint in a v4 codebase (form 500'd
  on name blur)
- `Review` — `is_visible` missing from `#[Fillable]`, making the admin's Hide/Show
  action a silent no-op
- `BookingPolicy::view()` — no admin bypass, so the panel's View action 403'd on
  every booking an admin didn't personally own
- `BookingTest` / `NotificationTest` — hardcoded mid-2026 check-in dates that had
  since passed `after_or_equal:today`

---

## 1. Delete the merged feature branch

`feature/backend-test-coverage` still exists locally and on `origin` after the PR #8
merge.

- [ ] `git branch -d feature/backend-test-coverage`
- [ ] `git push origin --delete feature/backend-test-coverage`

Trivial cleanup, no risk. Only reason it's still here is that nobody asked for it.

---

## 2. Require the CI gate via branch protection

The coverage gate runs on every PR but is currently **advisory** — nothing stops a
red build from being merged into `main`. Branch protection is a repo setting, not a
code change, so it could not ship inside PR #8.

- [ ] Require status checks to pass before merging on `main`
- [ ] Mark both jobs as required: `Backend (PHPUnit + coverage)` and
      `Frontend (ESLint + Vitest)`
- [ ] Decide whether to also require branches be up to date before merging

Can be done through the GitHub UI (Settings → Branches → Add rule) or `gh api`.
Needs admin rights on `beesmf96/pet-hotel`.

**Note:** turning this on makes the 95% `MIN_COVERAGE` floor in
`.github/workflows/ci.yml` genuinely blocking. That's the intent, but it's worth
being deliberate about — it's the point at which the gate starts costing people time.

---

## 3. Cover `PhotosRelationManager`'s FileUpload path

The last real coverage gap. Everything else outstanding is single unhit branches.

Current state (as of commit `3309816`):

| Coverage | File |
|---|---|
| 62.1% (18/29) | `Filament/Resources/HotelResource/RelationManagers/PhotosRelationManager.php` |
| 92.6% (25/27) | `Models/Booking.php` |
| 93.3% (14/15) | `Models/User.php` |
| 94.1% (16/17) | `UserResource/RelationManagers/PetsRelationManager.php` |
| 96.6% (28/29) | `HotelResource/RelationManagers/OwnersRelationManager.php` |
| 98.6% (73/74) | `HotelOwner/Resources/BookingResource.php` |
| 98.7% (77/78) | `Resources/BookingResource.php` |

The uncovered 11 lines in `PhotosRelationManager` are the `CreateAction` /
`EditAction` paths, which run a `FileUpload` component. Testing them needs
`Storage::fake()` plus an `UploadedFile::fake()->image(...)` fixture — the existing
tests only assert the table lists photos, which is why the write paths are cold.

- [x] Fake the upload disk — via a `fakeUploadDisk()` helper, not `setUp()`, since
      only the write-path tests need it
- [x] Create a photo through `callTableAction('create', data: [...])` with a fake image
- [x] Edit an existing photo's `sort_order`
- [x] Assert the uploaded file lands on the fake disk
- [x] Cover `reorderable('sort_order')` — plus a delete test, which was also cold

Two things differed from the plan's assumptions:

- **The disk is not `public`.** `FileUpload` has no explicit `->disk()`, so it falls
  through to `filesystems.default`, which is `local` under test. `Storage::fake('public')`
  passes the create action but asserts against a disk nothing was written to. The
  helper reads `config('filesystems.default')` so it tracks the real config.
- **Reorder is 1-based.** `reorderTable` is a Livewire method, not a Filament test
  helper (`->call('reorderTable', [...])`), and it writes positions starting at 1.

Existing tests to extend live in `tests/Feature/Filament/Admin/HotelResourceTest.php`.

## Acceptance Criteria

- [ ] Merged feature branch deleted locally and on `origin` — **deferred**, maintainer's call
- [ ] Both CI jobs required on `main` via branch protection — **deferred**, maintainer's call
- [x] `PhotosRelationManager` above the 95% floor, ideally at 100% — **100% (29/29)**
- [x] `composer test` still passes and overall line coverage has not regressed —
      315 tests pass, overall lines 98.36% → **99.36% (1091/1098)**

## Open Questions

- ~~Should `MIN_COVERAGE` be raised from 95 once item 3 lands?~~ **Decided 2026-08-01:
  leave at 95.** Coverage now sits at 99.36%, so the floor has ~4 points of slack; the
  call was that a gate tripping on legitimate work costs more than the slack does.
- Items 1 and 2 need repo admin rights and are the maintainer's call rather than
  something to automate. Both left untouched on purpose — `feature/backend-test-coverage`
  still exists locally and on `origin`, and `main` is still unprotected.
