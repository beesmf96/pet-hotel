<?php

namespace Tests\Feature\Filament\HotelOwner;

use App\Filament\HotelOwner\Resources\BookingResource\Pages\ListBookings;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class BookingResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private PetHotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('hotel-owner');

        $this->hotel = PetHotel::factory()->create();
        $this->owner = User::factory()->create();
        $this->hotel->owners()->attach($this->owner, ['role' => 'owner']);

        $this->actingAs($this->owner);
    }

    // ── Scoping ───────────────────────────────────────────────────────────────

    public function test_list_page_renders_bookings_for_the_owned_hotel(): void
    {
        $bookings = Booking::factory()->count(2)->for($this->hotel, 'hotel')->create();

        Livewire::test(ListBookings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($bookings);
    }

    public function test_list_page_hides_bookings_from_other_hotels(): void
    {
        $mine = Booking::factory()->for($this->hotel, 'hotel')->create();
        $theirs = Booking::factory()->create();

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_list_page_can_filter_by_status(): void
    {
        $pending = Booking::factory()->for($this->hotel, 'hotel')->create(['status' => 'pending']);
        $confirmed = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$confirmed]);
    }

    public function test_list_page_can_search_by_guest_name(): void
    {
        $guest = User::factory()->create(['name' => 'Marguerite']);
        $match = Booking::factory()->for($this->hotel, 'hotel')->for($guest)->create();
        $other = Booking::factory()->for($this->hotel, 'hotel')->create();

        Livewire::test(ListBookings::class)
            ->searchTable('Marguerite')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    // ── Record actions ────────────────────────────────────────────────────────

    public function test_confirm_action_confirms_a_pending_booking(): void
    {
        Queue::fake();

        $booking = Booking::factory()->for($this->hotel, 'hotel')->create(['status' => 'pending']);

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $booking)
            ->assertHasNoTableActionErrors();

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_decline_action_cancels_a_pending_booking(): void
    {
        Queue::fake();

        $booking = Booking::factory()->for($this->hotel, 'hotel')->create(['status' => 'pending']);

        Livewire::test(ListBookings::class)
            ->callTableAction('decline', $booking)
            ->assertHasNoTableActionErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_actions_are_hidden_for_non_pending_bookings(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('confirm', $booking)
            ->assertTableActionHidden('decline', $booking);
    }

    // ── Owner without a hotel ─────────────────────────────────────────────────

    public function test_user_with_no_hotel_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListBookings::class)->assertForbidden();
    }
}
