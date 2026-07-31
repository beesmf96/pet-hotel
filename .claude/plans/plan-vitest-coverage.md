---
plan: vitest-coverage
status: done
branch: feature/vitest-coverage
pr: https://github.com/beesmf96/pet-hotel/pull/3
implemented: 2026-05-30
---

# Feature: Vitest Branch Coverage

## What & Why

The frontend test suite has only 3 test files covering ~16 tests total. Large portions of the Vue component tree — including every Bookings page, Pets, Search, Profile, and key reusable components — have zero Vitest coverage. The tester agent explicitly requires a spec per conditional branch. This plan closes that gap by adding 10 new test files targeting every meaningful `v-if`/`v-else` branch, and wires up `@vitest/coverage-v8` so coverage is measurable going forward.

## Scope

- **10 new Vitest test files** covering conditional rendering branches across pages and components (see list below)
- **`@vitest/coverage-v8`** installed and configured in `vite.config.js` so `bun run test --coverage` works
- All tests follow the shape in `tester.md`: stub Inertia, stub child components not under test, one test per branch

## Out of Scope

- Auth pages (`Login`, `Register`, `ForgotPassword`, `ResetPassword`, `VerifyEmail`) — minimal branching, no conditional rendering driven by props
- `Dashboard.vue` — no conditional branches
- `BookingConfirmationPage.vue` — no conditional branches, pure display
- `NotificationBell.vue` / `NotificationsDropdown.vue` / `AvailabilityCalendar.vue` — deferred; their `fetch`-based async lifecycle makes them higher-effort to test correctly without a broader mocking strategy decision
- `SearchBar.vue` / `FilterSidebar.vue` — no rendering branches (emit-only behaviour)
- `HotelProfilePage.vue` — already covered by existing `HotelProfilePage.test.js`
- `HotelMap.vue` — already covered by existing `HotelMap.test.js`
- `Landing.vue` — already covered by existing `Landing.test.js`
- No changes to PHP feature tests

## Technical Approach

### Backend
_None — this is a frontend-only change._

### Frontend

#### Package

Install one dev dependency:
```bash
bun add --dev @vitest/coverage-v8
```

#### `vite.config.js` — add coverage config

Extend the existing `test` block:
```js
test: {
    environment: 'jsdom',
    globals: true,
    coverage: {
        provider: 'v8',
        reporter: ['text', 'html'],
        include: ['resources/js/**/*.vue', 'resources/js/composables/**/*.js'],
        exclude: ['resources/js/app.js', 'resources/js/bootstrap.js'],
    },
},
```

#### New test files (10 files)

All new files follow the stub pattern: `vi.mock('@/Layouts/AppLayout.vue', ...)` and `vi.mock('@inertiajs/vue3', ...)`. For pages using `useForm`, mock it to return `{ post: vi.fn(), patch: vi.fn(), processing: false, errors: {}, reset: vi.fn(), clearErrors: vi.fn(), recentlySuccessful: false }`.

---

**1. `resources/js/tests/Pages/Bookings/BookingDetailPage.test.js`**

Branches to cover:
- `flash.success` banner is shown when `flash.success` is truthy
- `flash.success` banner is absent when `flash` is empty
- Notes section shown when `booking.notes` is non-empty
- Notes section absent when `booking.notes` is null/undefined
- Cancel Booking button shown when `booking.status === 'pending'`
- Cancel Booking button absent when `booking.status === 'confirmed'`
- Status badge text: `Pending` / `Confirmed` / `Cancelled`

Stubs needed: `AppLayout`, `@inertiajs/vue3` (`usePage` returning `{ props: { flash: {} } }` or `{ props: { flash: { success: '...' } } }`, `router`)

---

**2. `resources/js/tests/Pages/Bookings/BookingFormPage.test.js`**

Branches to cover:
- No-pets warning (`bg-amber-50` block) shown when `pets = []`
- Form shown when `pets` array is non-empty
- Price summary placeholder ("Select a pet and dates") shown when `totalPrice` is null (no pet / no dates)
- Price summary calculation rows shown when `totalPrice` is computed
- "No pricing for species" warning shown when a pet is selected but the hotel has no matching `pet_type` pricing

Stubs needed: `AppLayout`, `@inertiajs/vue3` (`useForm` returning a form object with reactive `pet_id`, `check_in`, `check_out`)

---

**3. `resources/js/tests/Pages/Bookings/MyBookingsPage.test.js`**

Branches to cover:
- Empty state ("You have no bookings yet") shown when `bookings = []`
- Booking list shown when `bookings` is non-empty
- "Leave a review" button shown for `status === 'completed'` and `has_review === false`
- "Review submitted" text shown for `status === 'completed'` and `has_review === true`
- Neither prompt shown for `status === 'confirmed'` (not completed)
- `LeaveReviewModal` present in DOM when a booking is passed

Stubs needed: `AppLayout`, `Link`, `LeaveReviewModal`, `@inertiajs/vue3`, `@/composables/useFormatDate.js`

---

**4. `resources/js/tests/Pages/Pets.test.js`**

Branches to cover:
- Empty state shown when `pets = []`
- Pet grid shown when `pets` has entries
- Pet photo `<img>` shown when `pet.photo_url` is set
- Placeholder emoji shown when `pet.photo_url` is null/undefined
- Breed span shown when `pet.breed` is non-empty
- Breed span absent when `pet.breed` is empty/null
- Age shown when `pet.age != null`
- Age absent when `pet.age === null`
- Special needs text shown when `pet.special_needs` is set

Stubs needed: `AppLayout`, `PetFormModal`, `@inertiajs/vue3` (`router.delete`)

---

**5. `resources/js/tests/Pages/Hotels/SearchPage.test.js`**

Branches to cover:
- Hotel grid rendered when `hotels.data.length > 0`
- Empty state ("No hotels found") shown when `hotels.data.length === 0`
- Pagination buttons rendered when `hotels.last_page > 1`
- Pagination absent when `hotels.last_page === 1`

Stubs needed: `AppLayout`, `HotelCard`, `SearchBar`, `FilterSidebar`, `@inertiajs/vue3` (`router`)

---

**6. `resources/js/tests/Pages/Hotels/ReviewsPage.test.js`**

Branches to cover:
- Pagination controls shown when `reviews.last_page > 1`
- Pagination absent when `reviews.last_page === 1`
- "← Previous" is a `<Link>` when `reviews.prev_page_url` is set, a plain `<span>` when null
- "Next →" is a `<Link>` when `reviews.next_page_url` is set, a plain `<span>` when null

Stubs needed: `AppLayout`, `ReviewList`, `Link` (from Inertia), `@inertiajs/vue3`

---

**7. `resources/js/tests/Pages/Profile.test.js`**

Branches to cover:
- "Saved!" success message shown when `form.recentlySuccessful` is true
- "Saved!" message absent when `form.recentlySuccessful` is false
- Email field is disabled and displays `user.email`

Stubs needed: `AppLayout`, `@inertiajs/vue3` (`useForm` returns object with `recentlySuccessful`)

---

**8. `resources/js/tests/Components/Hotels/HotelCard.test.js`**

Branches to cover:
- `<img>` shown when `hotel.cover_photo` is set
- Placeholder emoji `🏨` shown when `hotel.cover_photo` is null
- Facilities list shown when `hotel.facilities.length > 0`
- "+N more" badge shown when `hotel.facilities.length > 3`
- "+N more" absent when `hotel.facilities.length <= 3`
- Price shown ("From $X.XX") when `hotel.price_from` is set
- "Pricing unavailable" shown when `hotel.price_from` is null
- Rating value shown when `hotel.reviews_avg_rating` is set
- Rating "—" shown when `hotel.reviews_avg_rating` is null

Stubs needed: `@inertiajs/vue3` (`router.visit`)

---

**9. `resources/js/tests/Components/PetFormModal.test.js`**

Branches to cover:
- Modal content absent from DOM when `show = false`
- Modal content present in DOM when `show = true`
- Title reads "Add Pet" when `pet` prop is null
- Title reads "Edit Pet" when `pet` prop is an object
- Submit button text is "Add Pet" when `pet` is null
- Submit button text is "Save Changes" when `pet` is set

Stubs needed: `@inertiajs/vue3` (`useForm`)

---

**10. `resources/js/tests/Components/Hotels/ReviewList.test.js`**

Branches to cover:
- Rating summary row (stars + count) shown when `averageRating` is truthy
- "No reviews yet" text shown when `averageRating` is null
- Review items rendered for each entry in `reviews`
- Comment paragraph shown when `review.comment` is non-empty
- Comment paragraph absent when `review.comment` is empty/null
- Review count singular ("1 review") vs plural ("2 reviews")

Stubs needed: `@/composables/useFormatDate.js` (or use real — it's a pure function)

## Acceptance Criteria

- [x] `bun run test` passes with all 10 new test files and no regressions to existing 3
- [x] `bun run test --coverage` runs without error and produces an HTML report under `coverage/`
- [x] Every branch listed above has at least one test asserting the present/absent element
- [x] No test uses `fetch`, `axios`, or real HTTP — all side-effectful child components are stubbed
- [x] No test directly asserts Tailwind class names (assert text content, element existence, or `data-testid` attributes instead)

## Edge Cases

- **`BookingFormPage` computed price**: `useForm` must be mocked with a reactive object so the computed `pricing`/`nights`/`totalPrice` work correctly when form fields change. Use `reactive()` for the form mock values rather than static objects if `v-model` is bound.
- **`PetFormModal` Teleport**: `@vue/test-utils` does not mount Teleport targets by default. Use `attachTo: document.body` so the teleported content is accessible via `wrapper.find(...)` or inspect `document.body` directly.
- **`MyBookingsPage` modal**: `LeaveReviewModal` uses Teleport too — stub it completely so its internals don't interfere.
- **`ReviewList` `useFormatDate`**: The composable is a pure function with no side effects; it can be used directly without mocking.
- **`HotelCard` `router.visit`**: Mock `router` from `@inertiajs/vue3` — the card calls `router.visit()` on click, which would throw in jsdom without a mock.

## Open Questions

_None — all decisions resolved before plan was written._
