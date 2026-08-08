<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploads have to follow filesystems.photos wherever it points, because on
 * ephemeral hosting it points at a bucket. Writes and URL generation previously
 * named their disks independently and only agreed by coincidence — both landed
 * on the same /storage path — so these pin them together.
 */
class UploadDiskTest extends TestCase
{
    use RefreshDatabase;

    private const DISK = 'test-bucket';

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.photos' => self::DISK]);

        // The base URL goes through fake()'s config argument rather than
        // config(): fake() builds the disk from scratch and ignores whatever is
        // already under filesystems.disks.
        Storage::fake(self::DISK, ['url' => 'https://cdn.example.com']);
        Storage::fake('public');
    }

    public function test_a_new_photo_lands_on_the_configured_disk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/pets', [
            'name' => 'Buddy',
            'species' => 'Dog',
            'photo' => UploadedFile::fake()->image('buddy.jpg'),
        ]);

        $photo = $user->pets()->sole()->photo;

        Storage::disk(self::DISK)->assertExists($photo);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_the_index_url_is_built_from_the_configured_disk(): void
    {
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('buddy.jpg')->store('pet-photos', self::DISK);
        $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'photo' => $photo]);

        $this->actingAs($user)
            ->get('/pets')
            ->assertInertia(fn ($page) => $page
                ->where('pets.0.photo_url', 'https://cdn.example.com/'.$photo)
            );
    }

    /**
     * A replaced photo that is deleted from the wrong disk is an object leaked
     * on the bucket, paid for and never referenced again.
     */
    public function test_replacing_a_photo_deletes_the_old_one_from_the_configured_disk(): void
    {
        $user = User::factory()->create();
        $old = UploadedFile::fake()->image('old.jpg')->store('pet-photos', self::DISK);
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'photo' => $old]);

        $this->actingAs($user)->patch("/pets/{$pet->id}", [
            'name' => 'Buddy',
            'species' => 'Dog',
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ]);

        Storage::disk(self::DISK)->assertMissing($old);
        Storage::disk(self::DISK)->assertExists($pet->fresh()->photo);
    }

    public function test_deleting_a_pet_removes_its_photo_from_the_configured_disk(): void
    {
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('buddy.jpg')->store('pet-photos', self::DISK);
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'Dog', 'photo' => $photo]);

        $this->actingAs($user)->delete("/pets/{$pet->id}");

        Storage::disk(self::DISK)->assertMissing($photo);
    }
}
