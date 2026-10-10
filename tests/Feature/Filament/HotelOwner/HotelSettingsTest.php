<?php

namespace Tests\Feature\Filament\HotelOwner;

use App\Filament\HotelOwner\Pages\HotelSettings;
use App\Models\PetHotel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HotelSettingsTest extends TestCase
{
    use RefreshDatabase;

    private PetHotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('hotel-owner');

        $this->hotel = PetHotel::factory()->create(['capacity' => 6]);
        $owner = User::factory()->create();
        $this->hotel->owners()->attach($owner, ['role' => 'owner']);

        $this->actingAs($owner);
    }

    public function test_form_is_filled_from_the_owned_hotel(): void
    {
        $this->hotel->policy()->create(['check_in_time' => '14:00:00', 'check_out_time' => '12:00:00']);

        Livewire::test(HotelSettings::class)
            ->assertSuccessful()
            ->assertSchemaStateSet([
                'capacity' => 6,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
            ]);
    }

    public function test_save_updates_capacity_and_times(): void
    {
        $this->hotel->policy()->create(['check_in_time' => '14:00', 'check_out_time' => '12:00', 'cancellation_policy' => 'Free until 48h before.']);

        Livewire::test(HotelSettings::class)
            ->fillForm(['capacity' => 4, 'check_in_time' => '15:00', 'check_out_time' => '11:00'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Hotel settings saved');

        $this->hotel->refresh();
        $this->assertSame(4, $this->hotel->capacity);
        $this->assertSame(['check_in' => '3:00 PM', 'check_out' => '11:00 AM'], $this->hotel->policy->stayTimes());
        $this->assertSame('Free until 48h before.', $this->hotel->policy->cancellation_policy);
    }

    public function test_save_creates_the_policy_when_missing(): void
    {
        Livewire::test(HotelSettings::class)
            ->fillForm(['capacity' => 6, 'check_in_time' => '13:00', 'check_out_time' => '10:00'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['check_in' => '1:00 PM', 'check_out' => '10:00 AM'], $this->hotel->fresh()->policy->stayTimes());
    }

    public function test_save_validates_the_fields(): void
    {
        Livewire::test(HotelSettings::class)
            ->fillForm(['capacity' => -1, 'check_in_time' => null, 'check_out_time' => null])
            ->call('save')
            ->assertHasFormErrors(['capacity' => 'min', 'check_in_time' => 'required', 'check_out_time' => 'required']);
    }

    public function test_save_changes_only_the_owned_hotel(): void
    {
        $other = PetHotel::factory()->create(['capacity' => 9]);

        Livewire::test(HotelSettings::class)
            ->fillForm(['capacity' => 2, 'check_in_time' => '13:00', 'check_out_time' => '10:00'])
            ->call('save');

        $this->assertSame(9, $other->fresh()->capacity);
        $this->assertNull($other->fresh()->policy);
    }

    public function test_page_is_forbidden_without_an_owned_hotel(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(HotelSettings::class)->assertForbidden();
    }
}
