<?php

namespace Tests\Feature;

use App\Filament\HotelOwner\Resources\BookingResource;
use App\Jobs\NotifyOwnersOfBookingRequest;
use App\Jobs\NotifyOwnersOfGuestCancellation;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\User;
use App\Notifications\BookingCancelledByGuest;
use App\Notifications\NewBookingRequest;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OwnerNotificationTest extends TestCase
{
    use RefreshDatabase;

    private PetHotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = PetHotel::factory()->create(['name' => 'Happy Paws']);
    }

    private function booking(array $attributes = []): Booking
    {
        $guest = User::factory()->create(['name' => 'Ada']);
        $pet = $guest->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        return Booking::factory()->for($guest)->for($this->hotel, 'hotel')->create([
            'pet_id' => $pet->id,
            'check_in' => '2030-10-24',
            'check_out' => '2030-10-26',
            'total_price' => 120,
            'status' => 'pending',
            ...$attributes,
        ]);
    }

    // ── Dispatch ──────────────────────────────────────────────────────────────

    public function test_a_booking_request_queues_the_owner_notice(): void
    {
        Queue::fake();

        $guest = User::factory()->create();
        $pet = $guest->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        $this->actingAs($guest)->post("/hotels/{$this->hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => now()->addDays(30)->toDateString(),
            'check_out' => now()->addDays(32)->toDateString(),
        ])->assertSessionHasNoErrors();

        Queue::assertPushed(NotifyOwnersOfBookingRequest::class);
    }

    public function test_a_guest_cancelling_queues_the_owner_notice(): void
    {
        Queue::fake();

        $booking = $this->booking();

        $this->actingAs($booking->user)->patch("/bookings/{$booking->id}/cancel");

        Queue::assertPushed(NotifyOwnersOfGuestCancellation::class, fn ($job) => $job->booking->is($booking));
    }

    public function test_an_owner_declining_does_not_notify_the_owners(): void
    {
        Queue::fake();

        $this->booking()->update(['status' => 'cancelled']);

        Queue::assertNotPushed(NotifyOwnersOfGuestCancellation::class);
    }

    // ── Jobs ──────────────────────────────────────────────────────────────────

    public function test_request_job_notifies_every_owner_of_the_hotel_only(): void
    {
        Notification::fake();

        $owners = User::factory()->count(2)->hotelOwner($this->hotel)->create();
        $otherOwner = User::factory()->hotelOwner(PetHotel::factory()->create())->create();
        $booking = $this->booking();

        (new NotifyOwnersOfBookingRequest($booking))->handle();

        Notification::assertSentTo($owners, NewBookingRequest::class);
        Notification::assertNotSentTo([$otherOwner, $booking->user], NewBookingRequest::class);
    }

    public function test_cancellation_job_notifies_the_owners(): void
    {
        Notification::fake();

        $owner = User::factory()->hotelOwner($this->hotel)->create();
        $booking = $this->booking(['status' => 'cancelled']);

        (new NotifyOwnersOfGuestCancellation($booking))->handle();

        Notification::assertSentTo($owner, BookingCancelledByGuest::class);
    }

    public function test_jobs_do_nothing_for_a_hotel_without_owners(): void
    {
        Notification::fake();

        (new NotifyOwnersOfBookingRequest($this->booking()))->handle();

        Notification::assertNothingSent();
    }

    public function test_jobs_retry_and_wait_for_the_commit(): void
    {
        $job = new NotifyOwnersOfBookingRequest($this->booking());

        $this->assertSame(3, $job->tries);
        $this->assertTrue($job->deleteWhenMissingModels);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $job);
    }

    // ── Content ───────────────────────────────────────────────────────────────

    public function test_request_mail_describes_the_stay(): void
    {
        $owner = User::factory()->create(['name' => 'Olive']);
        $booking = $this->booking(['notes' => 'Needs medication at 8am']);

        $mail = (new NewBookingRequest($booking))->toMail($owner);
        $lines = implode(' ', $mail->introLines);

        $this->assertSame('New booking request — Happy Paws', $mail->subject);
        $this->assertSame('Hi Olive,', $mail->greeting);
        $this->assertStringContainsString('Guest: Ada', $lines);
        $this->assertStringContainsString('Pet: Buddy (Dog)', $lines);
        $this->assertStringContainsString('Thu, 24 Oct 2030 to Sat, 26 Oct 2030 (2 nights)', $lines);
        $this->assertStringContainsString('Total: RM 120.00', $lines);
        $this->assertStringContainsString('Notes: Needs medication at 8am', $lines);
        $this->assertSame('Open bookings', $mail->actionText);
        $this->assertSame(BookingResource::getUrl('index', panel: 'hotel-owner'), $mail->actionUrl);
    }

    public function test_mail_leaves_out_empty_notes_and_says_one_night(): void
    {
        $lines = implode(' ', (new NewBookingRequest($this->booking(['check_out' => '2030-10-25'])))
            ->toMail(new User(['name' => 'Olive']))->introLines);

        $this->assertStringNotContainsString('Notes:', $lines);
        $this->assertStringContainsString('(1 night)', $lines);
    }

    public function test_cancellation_mail_has_its_own_subject(): void
    {
        $mail = (new BookingCancelledByGuest($this->booking()))->toMail(new User(['name' => 'Olive']));

        $this->assertSame('Booking request cancelled — Happy Paws', $mail->subject);
        $this->assertStringContainsString('nothing for you to do', implode(' ', $mail->introLines));
    }

    public function test_database_payload_is_in_the_panel_format(): void
    {
        $payload = (new NewBookingRequest($this->booking()))->toDatabase(new User);

        $this->assertSame('filament', $payload['format']);
        $this->assertSame('New booking request', $payload['title']);
        $this->assertStringContainsString('Ada · Buddy (Dog)', $payload['body']);
        $this->assertSame(BookingResource::getUrl('index', panel: 'hotel-owner'), $payload['actions'][0]['url']);
    }

    // ── The two bells ─────────────────────────────────────────────────────────

    public function test_owner_notifications_stay_out_of_the_customer_bell(): void
    {
        $owner = User::factory()->hotelOwner($this->hotel)->create();
        $booking = $this->booking();

        $owner->notify(new NewBookingRequest($booking));
        $owner->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => 'customer',
            'data' => ['type' => 'booking_requested', 'message' => 'Your request was received.', 'url' => '/'],
        ]);

        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(1, $owner->customerNotifications()->count());

        $this->actingAs($owner)->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.message', 'Your request was received.');

        $this->actingAs($owner)->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('unread_notifications_count', 1));
    }

    public function test_marking_all_read_on_the_customer_site_leaves_the_panel_bell(): void
    {
        $owner = User::factory()->hotelOwner($this->hotel)->create();
        $owner->notify(new NewBookingRequest($this->booking()));

        $this->actingAs($owner)->postJson('/notifications/read-all')->assertOk();

        $this->assertSame(1, $owner->unreadNotifications()->count());
    }

    public function test_a_panel_notification_cannot_be_marked_read_from_the_customer_site(): void
    {
        $owner = User::factory()->hotelOwner($this->hotel)->create();
        $owner->notify(new NewBookingRequest($this->booking()));
        $id = $owner->notifications()->first()->id;

        $this->actingAs($owner)->patchJson("/notifications/{$id}/read")->assertNotFound();
    }

    public function test_the_owner_panel_has_a_notification_bell(): void
    {
        $this->assertTrue(Filament::getPanel('hotel-owner')->hasDatabaseNotifications());
    }

    // ── Pending badge ─────────────────────────────────────────────────────────

    public function test_bookings_menu_shows_the_owned_hotels_pending_count(): void
    {
        Filament::setCurrentPanel('hotel-owner');
        $owner = User::factory()->hotelOwner($this->hotel)->create();
        $this->actingAs($owner);

        $this->assertNull(BookingResource::getNavigationBadge());

        $this->booking();
        $this->booking();
        $this->booking(['status' => 'confirmed']);
        Booking::factory()->create(['status' => 'pending']);

        $this->assertSame('2', BookingResource::getNavigationBadge());
        $this->assertSame('warning', BookingResource::getNavigationBadgeColor());
        $this->assertNotEmpty(BookingResource::getNavigationBadgeTooltip());
    }
}
