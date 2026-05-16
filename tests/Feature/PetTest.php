<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PetTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_view_pets(): void
    {
        $this->get('/pets')->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_view_pets(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/pets')
            ->assertRedirect('/email/verify');
    }

    public function test_user_can_view_pets_page(): void
    {
        $user = User::factory()->create();
        $user->pets()->create(['name' => 'Fluffy', 'species' => 'Cat']);

        $this->actingAs($user)
            ->get('/pets')
            ->assertStatus(200);
    }

    public function test_user_only_sees_own_pets(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $user->pets()->create(['name' => 'Mine', 'species' => 'Dog']);
        $other->pets()->create(['name' => 'Theirs', 'species' => 'Cat']);

        $this->actingAs($user)
            ->get('/pets')
            ->assertInertia(fn ($page) => $page->has('pets', 1));
    }

    public function test_photo_url_is_mapped_in_index(): void
    {
        Storage::fake('public');
        $user  = User::factory()->create();
        $photo = UploadedFile::fake()->image('buddy.jpg')->store('pet-photos', 'public');
        $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'photo' => $photo]);

        $this->actingAs($user)
            ->get('/pets')
            ->assertInertia(fn ($page) => $page
                ->has('pets', 1)
                ->where('pets.0.photo_url', Storage::disk('public')->url($photo))
            );
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_create_pet(): void
    {
        $this->post('/pets', ['name' => 'Buddy', 'species' => 'Dog'])
            ->assertRedirect('/login');
    }

    public function test_user_can_create_pet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pets', ['name' => 'Buddy', 'species' => 'Dog'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Pet added.');

        $this->assertDatabaseHas('pets', ['user_id' => $user->id, 'name' => 'Buddy', 'species' => 'Dog']);
    }

    public function test_user_can_create_pet_with_all_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/pets', [
            'name'          => 'Buddy',
            'species'       => 'Dog',
            'breed'         => 'Labrador',
            'age'           => 3,
            'special_needs' => 'Gluten-free food only',
        ]);

        $this->assertDatabaseHas('pets', [
            'user_id'       => $user->id,
            'breed'         => 'Labrador',
            'age'           => 3,
            'special_needs' => 'Gluten-free food only',
        ]);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pets', ['species' => 'Dog'])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_species(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pets', ['name' => 'Buddy'])
            ->assertSessionHasErrors('species');
    }

    public function test_store_validates_age_is_integer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pets', ['name' => 'Buddy', 'species' => 'Dog', 'age' => 'old'])
            ->assertSessionHasErrors('age');
    }

    public function test_store_validates_age_is_not_negative(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pets', ['name' => 'Buddy', 'species' => 'Dog', 'age' => -1])
            ->assertSessionHasErrors('age');
    }

    public function test_store_with_photo_upload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/pets', [
            'name'    => 'Buddy',
            'species' => 'Dog',
            'photo'   => UploadedFile::fake()->image('buddy.jpg'),
        ]);

        $pet = Pet::where('user_id', $user->id)->first();
        $this->assertNotNull($pet->photo);
        Storage::disk('public')->assertExists($pet->photo);
    }

    public function test_store_rejects_non_image_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/pets', [
                'name'    => 'Buddy',
                'species' => 'Dog',
                'photo'   => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_update_pet(): void
    {
        $user = User::factory()->create();
        $pet  = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->patch("/pets/{$pet->id}", ['name' => 'Max', 'species' => 'Dog'])
            ->assertRedirect('/login');
    }

    public function test_user_can_update_own_pet(): void
    {
        $user = User::factory()->create();
        $pet  = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->actingAs($user)
            ->patch("/pets/{$pet->id}", ['name' => 'Max', 'species' => 'Dog'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Pet updated.');

        $this->assertDatabaseHas('pets', ['id' => $pet->id, 'name' => 'Max']);
    }

    public function test_user_cannot_update_others_pet(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $pet   = $other->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->actingAs($user)
            ->patch("/pets/{$pet->id}", ['name' => 'Max', 'species' => 'Dog'])
            ->assertForbidden();
    }

    public function test_update_requires_name(): void
    {
        $user = User::factory()->create();
        $pet  = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->actingAs($user)
            ->patch("/pets/{$pet->id}", ['species' => 'Dog'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_with_new_photo_replaces_old(): void
    {
        Storage::fake('public');
        $user     = User::factory()->create();
        $oldPhoto = UploadedFile::fake()->image('old.jpg')->store('pet-photos', 'public');
        $pet      = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'photo' => $oldPhoto]);

        $this->actingAs($user)->patch("/pets/{$pet->id}", [
            'name'    => 'Buddy',
            'species' => 'Dog',
            'photo'   => UploadedFile::fake()->image('new.jpg'),
        ]);

        Storage::disk('public')->assertMissing($oldPhoto);
        $this->assertNotEquals($oldPhoto, $pet->fresh()->photo);
        Storage::disk('public')->assertExists($pet->fresh()->photo);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_guest_cannot_delete_pet(): void
    {
        $user = User::factory()->create();
        $pet  = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->delete("/pets/{$pet->id}")->assertRedirect('/login');
    }

    public function test_user_can_delete_own_pet(): void
    {
        $user = User::factory()->create();
        $pet  = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->actingAs($user)
            ->delete("/pets/{$pet->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Pet removed.');

        $this->assertDatabaseMissing('pets', ['id' => $pet->id]);
    }

    public function test_user_cannot_delete_others_pet(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $pet   = $other->pets()->create(['name' => 'Buddy', 'species' => 'Dog']);

        $this->actingAs($user)
            ->delete("/pets/{$pet->id}")
            ->assertForbidden();
    }

    public function test_destroy_deletes_photo_from_storage(): void
    {
        Storage::fake('public');
        $user  = User::factory()->create();
        $photo = UploadedFile::fake()->image('buddy.jpg')->store('pet-photos', 'public');
        $pet   = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'photo' => $photo]);

        $this->actingAs($user)->delete("/pets/{$pet->id}");

        Storage::disk('public')->assertMissing($photo);
    }
}
