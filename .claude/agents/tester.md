---
name: tester
description: Writes and reviews PHPUnit and Vitest tests following existing test conventions. Use after new behaviour is implemented, or when asked to add test coverage or diagnose a failing test.
tools: Read, Write, Edit, Glob, Grep, Bash
model: sonnet
---

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
│   ├── Auth/         # Auth controllers grouped in a subdirectory
│   │   ├── AuthTest.php
│   │   ├── EmailVerificationTest.php
│   │   ├── GoogleAuthTest.php
│   │   └── PasswordResetTest.php
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

## OAuth tests (Socialite)

OAuth callbacks call an external service, so the Socialite facade must be mocked. Follow `tests/Feature/Auth/GoogleAuthTest.php` as the canonical example.

```php
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

$socialiteUser = Mockery::mock(SocialiteUser::class);
$socialiteUser->shouldReceive('getId')->andReturn('provider-uid-1');
$socialiteUser->shouldReceive('getEmail')->andReturn('user@example.com');
$socialiteUser->shouldReceive('getName')->andReturn('User');

$provider = Mockery::mock(AbstractProvider::class);
$provider->shouldReceive('user')->andReturn($socialiteUser);

Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
```

Every OAuth callback test file must cover:
1. **New user** — a user is created with the provider id, email is auto-verified, and the session is authenticated.
2. **Existing user matched by email** — the provider id is linked to the existing row; no duplicate is created.
3. **Existing user matched by provider id** — logged in directly; no DB write to identity columns.
4. **`InvalidStateException`** — `Socialite::driver(...)->user()` throws; the user is redirected to `login` and remains a guest (`assertGuest()`).
5. **Redirect endpoint** — `/auth/{provider}` returns a redirect (mock the provider's `redirect()` method).

## Vitest (Vue) tests

Located in `resources/js/tests/`. Minimal coverage exists — only `HotelMap.test.js`, `HotelProfilePage.test.js`, and `Landing.test.js`.

When a page component gains new conditional rendering branches driven by Inertia props (`v-if`/`v-else`), add a Vitest spec in `resources/js/tests/Pages/<PageName>.test.js` with one test per branch. PHP feature tests only verify that the server delivers the correct props — they do not exercise the Vue template. At minimum: mount the component with each prop variant, assert the expected elements are present, and assert the excluded elements are absent. Stub `usePage`, `router`, and any child components not under test.

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
