<?php

namespace Tests\Unit\Policies;

use App\Models\Pet;
use App\Models\User;
use App\Policies\PetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PetPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PetPolicy;
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_owner_can_update_their_pet(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->assertTrue($this->policy->update($user, $pet));
    }

    public function test_non_owner_cannot_update_pet(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pet = $owner->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->assertFalse($this->policy->update($other, $pet));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_pet(): void
    {
        $user = User::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->assertTrue($this->policy->delete($user, $pet));
    }

    public function test_non_owner_cannot_delete_pet(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pet = $owner->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->assertFalse($this->policy->delete($other, $pet));
    }

    public function test_policy_uses_id_comparison(): void
    {
        // Verify the policy compares IDs (not object identity)
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $pet = $user1->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        // Fresh copies from DB should still work
        $this->assertTrue($this->policy->update(User::find($user1->id), Pet::find($pet->id)));
        $this->assertFalse($this->policy->update(User::find($user2->id), Pet::find($pet->id)));
    }
}
