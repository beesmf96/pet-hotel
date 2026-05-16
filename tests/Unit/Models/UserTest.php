<?php

namespace Tests\Unit\Models;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_implements_must_verify_email(): void
    {
        $this->assertInstanceOf(MustVerifyEmail::class, new User());
    }

    public function test_user_has_pets_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->pets());
    }

    public function test_pets_relationship_returns_only_users_pets(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $user->pets()->create(['name' => 'Mine', 'species' => 'Dog']);
        $other->pets()->create(['name' => 'Theirs', 'species' => 'Cat']);

        $this->assertCount(1, $user->pets);
        $this->assertEquals('Mine', $user->pets->first()->name);
    }

    public function test_password_is_hashed_on_create(): void
    {
        $user = User::factory()->create(['password' => 'plain-password']);

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plain-password', $user->password));
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    public function test_password_is_hidden(): void
    {
        $user  = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_remember_token_is_hidden(): void
    {
        $user  = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_user_is_fillable_with_expected_attributes(): void
    {
        $user = User::factory()->create([
            'name'               => 'Alice',
            'phone'              => '555-1234',
            'preferred_location' => 'Central Park',
        ]);

        $this->assertEquals('Alice', $user->name);
        $this->assertEquals('555-1234', $user->phone);
        $this->assertEquals('Central Park', $user->preferred_location);
    }

    public function test_unverified_factory_state_sets_email_verified_at_null(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());
    }
}
