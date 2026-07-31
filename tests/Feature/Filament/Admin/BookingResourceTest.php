<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Filament\Resources\BookingResource\Pages\ViewBooking;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class BookingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->admin()->create());
    }

    // ── Listing ───────────────────────────────────────────────────────────────

    public function test_list_page_renders_bookings(): void
    {
        $bookings = Booking::factory()->count(3)->create();

        Livewire::test(ListBookings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($bookings);
    }

    public function test_list_page_can_filter_by_status(): void
    {
        $pending = Booking::factory()->create(['status' => 'pending']);
        $confirmed = Booking::factory()->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$confirmed]);
    }

    public function test_list_page_can_search_by_pet_name(): void
    {
        $match = Booking::factory()->create([
            'pet_id' => Pet::factory()->create(['name' => 'Bartholomew']),
        ]);
        $other = Booking::factory()->create([
            'pet_id' => Pet::factory()->create(['name' => 'Zephyr']),
        ]);

        Livewire::test(ListBookings::class)
            ->searchTable('Bartholomew')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    // ── Record actions ────────────────────────────────────────────────────────

    public function test_confirm_action_confirms_a_pending_booking(): void
    {
        Queue::fake();

        $booking = Booking::factory()->create(['status' => 'pending']);

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $booking)
            ->assertHasNoTableActionErrors();

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_cancel_action_cancels_a_booking(): void
    {
        Queue::fake();

        $booking = Booking::factory()->create(['status' => 'pending']);

        Livewire::test(ListBookings::class)
            ->callTableAction('cancel', $booking)
            ->assertHasNoTableActionErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_confirm_action_is_hidden_for_non_pending_bookings(): void
    {
        $booking = Booking::factory()->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('confirm', $booking);
    }

    public function test_cancel_action_is_hidden_for_cancelled_bookings(): void
    {
        $booking = Booking::factory()->create(['status' => 'cancelled']);

        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('cancel', $booking);
    }

    // ── View page ─────────────────────────────────────────────────────────────

    public function test_view_page_renders_a_booking(): void
    {
        $booking = Booking::factory()->create();

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_view_page_renders_a_booking_the_admin_owns(): void
    {
        $booking = Booking::factory()->for(auth()->user())->create();

        Livewire::test(ViewBooking::class, ['record' => $booking->getRouteKey()])
            ->assertSuccessful();
    }
}
