<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetTest extends TestCase
{
    use RefreshDatabase;

    public function test_pet_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->assertInstanceOf(BelongsTo::class, $pet->user());
    }

    public function test_pet_belongs_to_correct_user(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->assertEquals($user->id, $pet->user->id);
    }

    public function test_pet_is_fillable_with_expected_attributes(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create([
            'name' => 'Buddy',
            'species' => 'Dog',
            'breed' => 'Labrador',
            'age' => 3,
            'special_needs' => 'Hypoallergenic food',
            'photo' => 'pet-photos/buddy.jpg',
        ]);

        $this->assertEquals('Buddy', $pet->name);
        $this->assertEquals('Dog', $pet->species);
        $this->assertEquals('Labrador', $pet->breed);
        $this->assertEquals(3, $pet->age);
        $this->assertEquals('Hypoallergenic food', $pet->special_needs);
        $this->assertEquals('pet-photos/buddy.jpg', $pet->photo);
    }

    public function test_pet_breed_is_nullable(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'breed' => null]);

        $this->assertNull($pet->breed);
    }

    public function test_pet_age_is_nullable(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'age' => null]);

        $this->assertNull($pet->age);
    }

    public function test_pet_special_needs_is_nullable(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'special_needs' => null]);

        $this->assertNull($pet->special_needs);
    }

    public function test_user_can_have_multiple_pets(): void
    {
        $user = User::factory()->create();
        $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);
        $user->pets()->create(['name' => 'Whiskers', 'species' => 'Cat']);

        $this->assertCount(2, $user->fresh()->pets);
    }
}
