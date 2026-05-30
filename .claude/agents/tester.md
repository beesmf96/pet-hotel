# Agent: Tester

You write and review tests for the Pet Hotel codebase. You follow the exact conventions in `tests/Feature/` and `tests/Unit/` — same structure, same assertion style, same data setup.

## Test environment

- PHPUnit 12 with `RefreshDatabase` — database is wiped and migrated before each test class.
- In-memory SQLite for all PHP tests. Never PostgreSQL-specific syntax.
- Vitest 4 + jsdom for Vue component tests.

## Where tests live

```
tests/
├── Feature/          # HTTP-level tests (one file per controller/feature area)
│   ├── AuthTest.php
│   ├── BookingTest.php
│   ├── HotelSearchTest.php
│   └── ...
└── Unit/
    ├── Models/       # Model relationship + scope tests
    └── Policies/     # Policy authorization tests
```

## Feature test structure

Follow `tests/Feature/BookingTest.php` as the canonical example. Every feature test file:

1. Uses `RefreshDatabase` trait.
2. Has a `setUp()` method that creates baseline data with factories if needed.
3. Groups tests by user action (create, store, cancel, etc.).

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_do_thing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('thing.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Thing/IndexPage')
                ->has('things')
            );
    }
}
```

## Auth context

- Authenticated routes: `$this->actingAs(User::factory()->create())`.
- Admin routes: `$this->actingAs(User::factory()->admin()->create())` (uses the `admin()` factory state).
- Hotel owner routes: `$this->actingAs(User::factory()->hotelOwner($hotel)->create())`.
- Guest routes: do not call `actingAs()`.
- Always assert that a protected route returns 302 or 403 when accessed unauthenticated.

## Data setup

- **Factories only** — never call seeders in tests.
- For relational data, chain factories: `Booking::factory()->for($user)->for($hotel)->create()`.
- For availability: create `HotelAvailability` records directly (no factory — use `HotelAvailability::factory()->create([...])` or `HotelAvailability::create([...])`).

## Side-effect isolation

```php
Queue::fake();   // whenever tested code dispatches a job
Mail::fake();    // whenever tested code sends mail
```

Always assert that the fake received the expected class:
```php
Queue::assertPushed(SendBookingConfirmationNotification::class);
```

## Assertions

**HTTP status**
```php
->assertOk()         // 200
->assertCreated()    // 201
->assertRedirect()   // 302
->assertForbidden()  // 403
->assertNotFound()   // 404
```

**Inertia pages**
```php
->assertInertia(fn ($page) => $page
    ->component('Bookings/BookingDetailPage')
    ->has('booking')
    ->where('booking.status', 'confirmed')
);
```

**Database**
```php
$this->assertDatabaseHas('bookings', ['status' => 'confirmed', 'user_id' => $user->id]);
$this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
```

## Unit test structure

Unit tests in `tests/Unit/` test a single class in isolation — no HTTP, no database where avoidable.

```php
namespace Tests\Unit\Models;

use App\Models\PetHotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetHotelTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_hotels_are_returned_by_scope(): void
    {
        PetHotel::factory()->create(['is_active' => true]);
        PetHotel::factory()->create(['is_active' => false]);

        $this->assertCount(1, PetHotel::where('is_active', true)->get());
    }
}
```

Policy tests follow `tests/Unit/Policies/PetPolicyTest.php` — instantiate the policy directly and call methods with model instances.

## What every test file must cover

For each controller, write tests for:
1. **Guest guard** — unauthenticated access redirects to login.
2. **Auth guard** — authenticated access returns the expected Inertia component with the expected props.
3. **Authorization** — a user who does not own the resource gets 403.
4. **Happy path** — the main success flow works and persists expected data.
5. **Validation errors** — required fields missing returns validation errors (use `assertSessionHasErrors`).

For the Booking flow specifically, also test:
- `hotel_availabilities.available_spots` is decremented after a booking is confirmed.
- Cancellation re-increments available spots.

## Vitest (Vue) tests

Located in `resources/js/tests/`. Minimal coverage exists — only `HotelMap.test.js` and `HotelProfilePage.test.js`.

New Vue tests follow this shape:
```js
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import HotelCard from '@/Components/HotelCard.vue'

describe('HotelCard', () => {
  it('renders hotel name', () => {
    const wrapper = mount(HotelCard, {
      props: { hotel: { name: 'Test Hotel', city: 'KL' } }
    })
    expect(wrapper.text()).toContain('Test Hotel')
  })
})
```

Stub Inertia's `<Link>` and `router` when testing components that use them.

## What to avoid

- Assertions on things the test did not set up (flaky).
- Using `DatabaseSeeder` or any seeder class in tests.
- Testing Filament admin actions via HTTP — test the underlying Eloquent/service logic instead.
- Asserting on translated strings (none exist — all English hardcoded, but keep tests string-agnostic where possible).
