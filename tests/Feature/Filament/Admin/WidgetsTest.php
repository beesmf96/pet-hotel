<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Widgets\BookingsTodayWidget;
use App\Filament\Widgets\NewUsersThisWeekWidget;
use App\Filament\Widgets\PendingBookingsWidget;
use App\Models\Booking;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_bookings_today_widget_counts_todays_check_ins(): void
    {
        Booking::factory()->count(2)->create(['check_in' => today(), 'check_out' => today()->addDays(2)]);
        Booking::factory()->create(['check_in' => today()->addDays(5), 'check_out' => today()->addDays(7)]);

        Livewire::test(BookingsTodayWidget::class)
            ->assertSuccessful()
            ->assertSee('Check-ins Today')
            ->assertSee('2');
    }

    public function test_bookings_today_widget_renders_zero_when_empty(): void
    {
        Livewire::test(BookingsTodayWidget::class)
            ->assertSuccessful()
            ->assertSee('0');
    }

    public function test_pending_bookings_widget_counts_only_pending(): void
    {
        Booking::factory()->count(3)->create(['status' => 'pending']);
        Booking::factory()->confirmed()->create();

        Livewire::test(PendingBookingsWidget::class)
            ->assertSuccessful()
            ->assertSee('Pending Bookings')
            ->assertSee('3');
    }

    public function test_new_users_widget_counts_only_the_last_seven_days(): void
    {
        // The admin from setUp() is itself a recent user, so count relative to that.
        User::factory()->count(2)->create();
        User::factory()->create(['created_at' => now()->subDays(30)]);

        Livewire::test(NewUsersThisWeekWidget::class)
            ->assertSuccessful()
            ->assertSee('New Users This Week')
            ->assertSee('3');
    }
}
