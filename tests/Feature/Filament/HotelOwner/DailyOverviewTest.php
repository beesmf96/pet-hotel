<?php

namespace Tests\Feature\Filament\HotelOwner;

use App\Filament\HotelOwner\Pages\DailyOverview;
use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyOverviewTest extends TestCase
{
    use RefreshDatabase;

    private PetHotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('hotel-owner');

        $this->hotel = PetHotel::factory()->create(['capacity' => 2]);
        $owner = User::factory()->create();
        $this->hotel->owners()->attach($owner, ['role' => 'owner']);

        $this->actingAs($owner);
    }

    private function nextMonth(int $day): string
    {
        return today()->startOfMonth()->addMonth()->day($day)->toDateString();
    }

    /** @return array<string, mixed> */
    private function row(string $date): array
    {
        $records = Livewire::test(DailyOverview::class)
            ->filterTable('month', substr($date, 0, 7))
            ->instance()
            ->getTableRecords();

        return $records[$date];
    }

    public function test_shows_every_night_of_the_current_month_by_default(): void
    {
        $records = Livewire::test(DailyOverview::class)
            ->assertSuccessful()
            ->instance()
            ->getTableRecords();

        $this->assertCount(today()->daysInMonth, $records);
        $this->assertArrayHasKey(today()->startOfMonth()->toDateString(), $records->all());
    }

    public function test_a_normal_night_shows_capacity_and_spots(): void
    {
        $this->assertSame(
            ['date' => $this->nextMonth(3), 'capacity' => 2, 'changed' => false, 'booked' => 0, 'spots_left' => 2, 'status' => 'available'],
            array_intersect_key($this->row($this->nextMonth(3)), array_flip(['date', 'capacity', 'changed', 'booked', 'spots_left', 'status'])),
        );
    }

    public function test_confirmed_bookings_are_counted_and_pending_ones_are_not(): void
    {
        Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => $this->nextMonth(5),
            'check_out' => $this->nextMonth(6),
        ]);
        Booking::factory()->for($this->hotel, 'hotel')->create([
            'check_in' => $this->nextMonth(5),
            'check_out' => $this->nextMonth(6),
            'status' => 'pending',
        ]);

        $row = $this->row($this->nextMonth(5));

        $this->assertSame(1, $row['booked']);
        $this->assertSame(1, $row['spots_left']);
        $this->assertSame('limited', $row['status']);
    }

    public function test_a_changed_and_a_closed_date_are_marked(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->nextMonth(8), 'capacity' => 1]);
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => $this->nextMonth(9), 'is_blocked' => true]);

        $changed = $this->row($this->nextMonth(8));
        $closed = $this->row($this->nextMonth(9));

        $this->assertTrue($changed['changed']);
        $this->assertSame(1, $changed['capacity']);
        $this->assertSame('blocked', $closed['status']);
        $this->assertNull($closed['spots_left']);
    }

    public function test_other_hotels_bookings_are_not_counted(): void
    {
        Booking::factory()->confirmed()->create([
            'check_in' => $this->nextMonth(5),
            'check_out' => $this->nextMonth(6),
        ]);

        $this->assertSame(0, $this->row($this->nextMonth(5))['booked']);
    }

    public function test_the_table_renders_its_labels(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => today()->toDateString(), 'is_blocked' => true]);

        Livewire::test(DailyOverview::class)
            ->assertSee('Closed')
            ->assertSee('Pending requests do not take a spot');
    }

    public function test_page_is_forbidden_without_an_owned_hotel(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DailyOverview::class)->assertForbidden();
    }
}
