<?php

namespace Tests\Feature\Filament\HotelOwner;

use App\Filament\HotelOwner\Resources\AvailabilityResource\Pages\ManageAvailability;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AvailabilityResourceTest extends TestCase
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

    private function override(string $date): ?HotelAvailability
    {
        return HotelAvailability::where('hotel_id', $this->hotel->id)->whereDate('date', $date)->first();
    }

    private function futureDate(int $days): string
    {
        return now()->addDays($days)->toDateString();
    }

    public function test_list_shows_only_the_owned_hotels_upcoming_changes(): void
    {
        $mine = HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->futureDate(3), 'is_blocked' => true]);
        $past = HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->futureDate(-3), 'is_blocked' => true]);
        $theirs = HotelAvailability::create(['hotel_id' => PetHotel::factory()->create()->id, 'date' => $this->futureDate(3), 'is_blocked' => true]);

        Livewire::test(ManageAvailability::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$past, $theirs]);
    }

    public function test_change_dates_closes_every_night_in_the_range(): void
    {
        Livewire::test(ManageAvailability::class)
            ->callAction('changeDates', data: [
                'from' => $this->futureDate(10),
                'until' => $this->futureDate(12),
                'is_blocked' => true,
            ])
            ->assertHasNoActionErrors();

        foreach ([10, 11, 12] as $day) {
            $row = $this->override($this->futureDate($day));
            $this->assertTrue($row->is_blocked);
            $this->assertNull($row->capacity);
        }
        $this->assertNull($this->override($this->futureDate(13)));
    }

    public function test_change_dates_sets_a_capacity_and_replaces_an_earlier_change(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->futureDate(10), 'is_blocked' => true]);

        Livewire::test(ManageAvailability::class)
            ->callAction('changeDates', data: [
                'from' => $this->futureDate(10),
                'until' => $this->futureDate(10),
                'is_blocked' => false,
                'capacity' => 2,
            ])
            ->assertHasNoActionErrors();

        $row = $this->override($this->futureDate(10));
        $this->assertFalse($row->is_blocked);
        $this->assertSame(2, $row->capacity);
        $this->assertSame(1, HotelAvailability::where('hotel_id', $this->hotel->id)->count());
    }

    public function test_change_dates_needs_a_capacity_unless_closed(): void
    {
        Livewire::test(ManageAvailability::class)
            ->callAction('changeDates', data: [
                'from' => $this->futureDate(10),
                'until' => $this->futureDate(10),
                'is_blocked' => false,
                'capacity' => null,
            ])
            ->assertHasActionErrors(['capacity' => 'required']);
    }

    public function test_change_dates_rejects_an_end_before_the_start(): void
    {
        Livewire::test(ManageAvailability::class)
            ->callAction('changeDates', data: [
                'from' => $this->futureDate(10),
                'until' => $this->futureDate(9),
                'is_blocked' => true,
            ])
            ->assertHasActionErrors(['until']);
    }

    public function test_reset_dates_removes_changes_in_the_range_only(): void
    {
        foreach ([10, 11, 12] as $day) {
            HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->futureDate($day), 'is_blocked' => true]);
        }
        $other = HotelAvailability::create(['hotel_id' => PetHotel::factory()->create()->id, 'date' => $this->futureDate(10), 'is_blocked' => true]);

        Livewire::test(ManageAvailability::class)
            ->callAction('resetDates', data: [
                'from' => $this->futureDate(10),
                'until' => $this->futureDate(11),
            ])
            ->assertHasNoActionErrors();

        $this->assertNull($this->override($this->futureDate(10)));
        $this->assertNull($this->override($this->futureDate(11)));
        $this->assertNotNull($this->override($this->futureDate(12)));
        $this->assertModelExists($other);
    }

    public function test_editing_a_date_to_closed_drops_its_capacity(): void
    {
        $row = HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->futureDate(5), 'capacity' => 2]);

        Livewire::test(ManageAvailability::class)
            ->callTableAction('edit', $row, data: ['is_blocked' => true])
            ->assertHasNoTableActionErrors();

        $row->refresh();
        $this->assertTrue($row->is_blocked);
        $this->assertNull($row->capacity);
    }

    public function test_reset_on_a_row_deletes_it(): void
    {
        $row = HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->futureDate(5), 'is_blocked' => true]);

        Livewire::test(ManageAvailability::class)
            ->callTableAction('delete', $row);

        $this->assertModelMissing($row);
    }

    public function test_page_is_forbidden_without_an_owned_hotel(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageAvailability::class)->assertForbidden();
    }
}
