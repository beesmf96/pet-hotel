<?php

namespace Tests\Feature\Filament\HotelOwner;

use App\Enums\CancelledBy;
use App\Filament\HotelOwner\Resources\BookingResource\Pages\ListBookings;
use App\Jobs\NotifyOwnersOfGuestCancellation;
use App\Jobs\SendBookingCancelledNotification;
use App\Jobs\SendBookingConfirmationNotification;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\PetHotel;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
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

    public function test_confirm_action_refuses_a_booking_that_no_longer_fits(): void
    {
        Queue::fake();

        $this->hotel->update(['capacity' => 1]);
        Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-02',
        ]);
        $booking = Booking::factory()->for($this->hotel, 'hotel')->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-03',
            'status' => 'pending',
        ]);

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $booking)
            ->assertNotified('Cannot confirm this booking');

        $this->assertSame('pending', $booking->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_confirm_action_refuses_a_pet_confirmed_at_another_hotel(): void
    {
        Queue::fake();

        $pet = Pet::factory()->create();
        Booking::factory()->for($pet)->confirmed()->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-03',
        ]);
        $booking = Booking::factory()->for($this->hotel, 'hotel')->for($pet)->create([
            'check_in' => '2030-08-02',
            'check_out' => '2030-08-04',
            'status' => 'pending',
        ]);

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $booking)
            ->assertNotified(
                Notification::make()
                    ->danger()
                    ->title('Cannot confirm this booking')
                    ->body('This pet already has a confirmed stay on at least one of these nights, here or at another hotel. A pet can only stay in one place a night, so decline this request.')
            );

        $this->assertSame('pending', $booking->fresh()->status);
        Queue::assertNothingPushed();
    }

    // ── Booking pop-up ────────────────────────────────────────────────────────

    public function test_view_pop_up_shows_the_stay_the_pet_and_the_guest(): void
    {
        $this->hotel->policy()->create(['check_in_time' => '14:00', 'check_out_time' => '12:00']);
        $guest = User::factory()->create(['name' => 'Ada Guest', 'email' => 'ada@example.com', 'phone' => '012-345 6789']);
        $pet = Pet::factory()->for($guest)->create([
            'name' => 'Buddy', 'species' => 'dog', 'breed' => 'Beagle', 'age' => 3, 'special_needs' => 'Pills at 8am',
        ]);
        $booking = Booking::factory()->for($this->hotel, 'hotel')->for($guest)->for($pet)->create([
            'check_in' => '2030-10-25',
            'check_out' => '2030-10-28',
            'status' => 'pending',
            'total_price' => 1950,
            'notes' => 'Please walk him twice',
        ]);

        Livewire::test(ListBookings::class)
            ->mountTableAction('view', $booking)
            ->assertMountedActionModalSee('Booking from Ada Guest')
            ->assertMountedActionModalSee('Fri, 25 Oct 2030, from 2:00 PM')
            ->assertMountedActionModalSee('Mon, 28 Oct 2030, by 12:00 PM')
            ->assertMountedActionModalSee('3 nights')
            ->assertMountedActionModalSee('RM 1,950.00')
            ->assertMountedActionModalSee('Please walk him twice')
            ->assertMountedActionModalSee('Beagle')
            ->assertMountedActionModalSee('Pills at 8am')
            ->assertMountedActionModalSee('ada@example.com')
            ->assertMountedActionModalSee('012-345 6789');
    }

    public function test_view_pop_up_without_hotel_times_or_optional_details(): void
    {
        $pet = Pet::factory()->create(['breed' => null, 'age' => null, 'special_needs' => null]);
        $booking = Booking::factory()->for($this->hotel, 'hotel')->for($pet)->create([
            'check_in' => '2030-10-25',
            'check_out' => '2030-10-26',
            'notes' => null,
        ]);

        Livewire::test(ListBookings::class)
            ->mountTableAction('view', $booking)
            ->assertMountedActionModalSee('Fri, 25 Oct 2030')
            ->assertMountedActionModalDontSee('from 2:00 PM')
            ->assertMountedActionModalSee('1 night');
    }

    public function test_confirm_from_the_pop_up_confirms_and_closes_it(): void
    {
        Queue::fake();

        $booking = Booking::factory()->for($this->hotel, 'hotel')->create(['status' => 'pending']);

        $component = Livewire::test(ListBookings::class)
            ->callAction([
                TestAction::make('view')->table($booking),
                TestAction::make('confirmFromView')->table($booking),
            ])
            ->assertHasNoActionErrors();

        $this->assertSame([], $component->instance()->mountedActions);
        $this->assertSame('confirmed', $booking->fresh()->status);
        Queue::assertPushed(SendBookingConfirmationNotification::class);
    }

    public function test_decline_from_the_pop_up_declines(): void
    {
        Queue::fake();

        $booking = Booking::factory()->for($this->hotel, 'hotel')->create(['status' => 'pending']);

        Livewire::test(ListBookings::class)
            ->callAction([
                TestAction::make('view')->table($booking),
                TestAction::make('declineFromView')->table($booking),
            ]);

        $this->assertSame('cancelled', $booking->fresh()->status);
        Queue::assertPushed(
            SendBookingCancelledNotification::class,
            fn ($job) => $job->by === CancelledBy::Hotel && ! $job->wasConfirmed,
        );
    }

    public function test_pop_up_has_no_confirm_or_decline_once_answered(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->assertActionHidden([
                TestAction::make('view')->table($booking),
                TestAction::make('confirmFromView')->table($booking),
            ])
            ->assertActionHidden([
                TestAction::make('view')->table($booking),
                TestAction::make('declineFromView')->table($booking),
            ]);
    }

    public function test_notification_link_opens_the_pop_up_for_that_booking(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->create();
        $booking->user->update(['name' => 'Ada Guest']);

        $component = Livewire::withQueryParams(['tableAction' => 'view', 'tableActionRecord' => $booking->id])
            ->test(ListBookings::class);

        // The page opens it from the browser once loaded (wire:init); do that step here.
        $component->call('mountAction', 'view', [], $component->instance()->getDefaultTableActionUrlContext())
            ->assertMountedActionModalSee('Booking from Ada Guest');
    }

    public function test_notification_link_does_not_open_another_hotels_booking(): void
    {
        $other = Booking::factory()->create();
        $other->user->update(['name' => 'Someone Else']);

        $component = Livewire::withQueryParams(['tableAction' => 'view', 'tableActionRecord' => $other->id])
            ->test(ListBookings::class);
        $component->call('mountAction', 'view', [], $component->instance()->getDefaultTableActionUrlContext());

        $this->assertSame([], $component->instance()->mountedActions);
    }

    public function test_decline_action_cancels_a_pending_booking(): void
    {
        Queue::fake();

        $booking = Booking::factory()->for($this->hotel, 'hotel')->create(['status' => 'pending']);

        Livewire::test(ListBookings::class)
            ->callTableAction('decline', $booking)
            ->assertHasNoTableActionErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
        Queue::assertPushed(SendBookingCancelledNotification::class, fn ($job) => $job->by === CancelledBy::Hotel);
        Queue::assertNotPushed(NotifyOwnersOfGuestCancellation::class);
    }

    public function test_actions_are_hidden_for_non_pending_bookings(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create();

        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('confirm', $booking)
            ->assertTableActionHidden('decline', $booking);
    }

    public function test_complete_action_completes_a_confirmed_booking_after_check_out(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => now()->subDays(3),
            'check_out' => now()->subDay(),
        ]);

        Livewire::test(ListBookings::class)
            ->callTableAction('complete', $booking)
            ->assertHasNoTableActionErrors();

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_complete_action_is_visible_on_the_check_out_day(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => now()->subDays(2),
            'check_out' => today(),
        ]);

        Livewire::test(ListBookings::class)
            ->assertTableActionVisible('complete', $booking);
    }

    public function test_complete_action_is_hidden_before_check_out(): void
    {
        $booking = Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => now()->addDay(),
            'check_out' => now()->addDays(3),
        ]);

        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('complete', $booking);
    }

    public function test_complete_action_is_hidden_for_non_confirmed_bookings(): void
    {
        $pending = Booking::factory()->for($this->hotel, 'hotel')->create([
            'status' => 'pending',
            'check_in' => now()->subDays(3),
            'check_out' => now()->subDay(),
        ]);
        $completed = Booking::factory()->for($this->hotel, 'hotel')->completed()->create();

        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('complete', $pending)
            ->assertTableActionHidden('complete', $completed);
    }

    // ── Owner without a hotel ─────────────────────────────────────────────────

    public function test_user_with_no_hotel_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListBookings::class)->assertForbidden();
    }
}
