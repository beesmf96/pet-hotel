<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\HotelResource\Pages\CreateHotel;
use App\Filament\Resources\HotelResource\Pages\EditHotel;
use App\Filament\Resources\HotelResource\Pages\ListHotels;
use App\Filament\Resources\HotelResource\RelationManagers\OwnersRelationManager;
use App\Filament\Resources\HotelResource\RelationManagers\PhotosRelationManager;
use App\Filament\Resources\HotelResource\RelationManagers\PricingRelationManager;
use App\Models\PetHotel;
use App\Models\PetHotelPhoto;
use App\Models\PetHotelPolicy;
use App\Models\PetHotelPricing;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HotelResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * The form's policy section requires check-in/check-out times, so a hotel being
     * edited needs a policy row or saving fails validation on untouched fields.
     */
    private function hotelWithPolicy(array $attrs = []): PetHotel
    {
        $hotel = PetHotel::factory()->create($attrs);

        PetHotelPolicy::create([
            'hotel_id' => $hotel->id,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'cancellation_policy' => 'Free within 48 hours.',
        ]);

        return $hotel;
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_list_page_renders_hotels(): void
    {
        $hotels = PetHotel::factory()->count(3)->create();

        Livewire::test(ListHotels::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($hotels);
    }

    public function test_list_page_can_search_by_name(): void
    {
        $match = PetHotel::factory()->create(['name' => 'Wagging Tails Retreat']);
        $other = PetHotel::factory()->create(['name' => 'Quiet Cattery']);

        Livewire::test(ListHotels::class)
            ->searchTable('Wagging Tails')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_list_page_can_filter_by_active_state(): void
    {
        $active = PetHotel::factory()->create(['is_active' => true]);
        $inactive = PetHotel::factory()->create(['is_active' => false]);

        Livewire::test(ListHotels::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_page_stores_a_hotel_with_its_policy(): void
    {
        Livewire::test(CreateHotel::class)
            ->fillForm([
                'name' => 'Paws Palace',
                'slug' => 'paws-palace',
                'description' => 'A calm place to stay.',
                'address' => '12 Bark Lane',
                'city' => 'Kuala Lumpur',
                'is_active' => true,
                'policy' => [
                    'check_in_time' => '14:00',
                    'check_out_time' => '12:00',
                    'cancellation_policy' => 'Free within 48 hours.',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pet_hotels', [
            'name' => 'Paws Palace',
            'slug' => 'paws-palace',
            'city' => 'Kuala Lumpur',
        ]);
    }

    public function test_create_page_requires_name_address_and_city(): void
    {
        Livewire::test(CreateHotel::class)
            ->fillForm(['name' => null, 'slug' => null, 'address' => null, 'city' => null])
            ->call('create')
            ->assertHasFormErrors(['name', 'slug', 'address', 'city']);
    }

    public function test_slug_must_be_unique(): void
    {
        PetHotel::factory()->create(['slug' => 'taken-slug']);

        Livewire::test(CreateHotel::class)
            ->fillForm([
                'name' => 'Another Hotel',
                'slug' => 'taken-slug',
                'address' => '9 Cat Street',
                'city' => 'Penang',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_edit_page_loads_existing_data(): void
    {
        $hotel = PetHotel::factory()->create(['name' => 'Original Name']);

        Livewire::test(EditHotel::class, ['record' => $hotel->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['name' => 'Original Name']);
    }

    public function test_edit_page_saves_changes(): void
    {
        $hotel = $this->hotelWithPolicy();

        Livewire::test(EditHotel::class, ['record' => $hotel->getRouteKey()])
            ->fillForm(['name' => 'Renamed Hotel'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed Hotel', $hotel->fresh()->name);
    }

    public function test_slug_uniqueness_ignores_the_record_being_edited(): void
    {
        $hotel = $this->hotelWithPolicy(['slug' => 'my-slug']);

        Livewire::test(EditHotel::class, ['record' => $hotel->getRouteKey()])
            ->fillForm(['name' => 'New Name', 'slug' => 'my-slug'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── Relation managers ─────────────────────────────────────────────────────

    public function test_photos_relation_manager_lists_photos(): void
    {
        $hotel = PetHotel::factory()->create();
        $photos = collect(['a.jpg', 'b.jpg'])->map(fn ($url, $i) => PetHotelPhoto::create([
            'hotel_id' => $hotel->id,
            'url' => $url,
            'sort_order' => $i,
        ]));

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($photos);
    }

    /**
     * The FileUpload fields are pinned to `filesystems.photos`, the one disk
     * every upload in the app goes through, so that is what has to be faked —
     * faking anything else asserts against a disk nothing was written to.
     */
    private function fakeUploadDisk(): string
    {
        $disk = config('filesystems.photos');
        Storage::fake($disk);

        return $disk;
    }

    public function test_photos_relation_manager_creates_a_photo(): void
    {
        $disk = $this->fakeUploadDisk();

        $hotel = PetHotel::factory()->create();

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->callTableAction('create', data: [
                'url' => [UploadedFile::fake()->image('kennel.jpg')],
                'sort_order' => 3,
            ])
            ->assertHasNoTableActionErrors();

        $photo = PetHotelPhoto::where('hotel_id', $hotel->id)->sole();

        $this->assertSame(3, (int) $photo->sort_order);
        Storage::disk($disk)->assertExists($photo->url);
    }

    public function test_photos_relation_manager_edits_sort_order(): void
    {
        $this->fakeUploadDisk();

        $hotel = PetHotel::factory()->create();
        $photo = PetHotelPhoto::create([
            'hotel_id' => $hotel->id,
            'url' => 'a.jpg',
            'sort_order' => 0,
        ]);

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->callTableAction('edit', $photo, data: [
                'url' => ['a.jpg'],
                'sort_order' => 7,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(7, (int) $photo->fresh()->sort_order);
    }

    public function test_photos_relation_manager_reorders_photos(): void
    {
        $hotel = PetHotel::factory()->create();
        $photos = collect(['a.jpg', 'b.jpg'])->map(fn ($url, $i) => PetHotelPhoto::create([
            'hotel_id' => $hotel->id,
            'url' => $url,
            'sort_order' => $i,
        ]));

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->call('reorderTable', [$photos[1]->getKey(), $photos[0]->getKey()])
            ->assertSuccessful();

        // Filament writes 1-based positions in the order the keys were passed.
        $this->assertSame(1, (int) $photos[1]->fresh()->sort_order);
        $this->assertSame(2, (int) $photos[0]->fresh()->sort_order);
    }

    public function test_photos_relation_manager_deletes_a_photo(): void
    {
        $hotel = PetHotel::factory()->create();
        $photo = PetHotelPhoto::create([
            'hotel_id' => $hotel->id,
            'url' => 'a.jpg',
            'sort_order' => 0,
        ]);

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->callTableAction('delete', $photo)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('pet_hotel_photos', ['id' => $photo->id]);
    }

    public function test_pricing_relation_manager_lists_pricing(): void
    {
        $hotel = PetHotel::factory()->create();
        $pricing = PetHotelPricing::create([
            'hotel_id' => $hotel->id,
            'pet_type' => 'dog',
            'price_per_night' => 60,
        ]);

        Livewire::test(PricingRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$pricing]);
    }

    public function test_pricing_relation_manager_creates_a_row(): void
    {
        $hotel = PetHotel::factory()->create();

        Livewire::test(PricingRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->callTableAction('create', data: [
                'pet_type' => 'cat',
                'price_per_night' => 45,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('pet_hotel_pricing', [
            'hotel_id' => $hotel->id,
            'pet_type' => 'cat',
        ]);
    }

    public function test_owners_relation_manager_lists_owners_with_role(): void
    {
        $hotel = PetHotel::factory()->create();
        $owner = User::factory()->create();
        $hotel->owners()->attach($owner, ['role' => 'manager']);

        Livewire::test(OwnersRelationManager::class, [
            'ownerRecord' => $hotel,
            'pageClass' => EditHotel::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$owner]);
    }
}
