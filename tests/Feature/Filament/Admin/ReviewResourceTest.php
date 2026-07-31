<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Filament\Resources\ReviewResource\Pages\ViewReview;
use App\Models\PetHotel;
use App\Models\Review;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_list_page_renders_reviews(): void
    {
        $reviews = Review::factory()->count(3)->create();

        Livewire::test(ListReviews::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($reviews);
    }

    public function test_list_page_shows_hidden_reviews_too(): void
    {
        $hidden = Review::factory()->hidden()->create();

        Livewire::test(ListReviews::class)
            ->assertCanSeeTableRecords([$hidden]);
    }

    public function test_list_page_can_filter_by_visibility(): void
    {
        $visible = Review::factory()->create();
        $hidden = Review::factory()->hidden()->create();

        Livewire::test(ListReviews::class)
            ->filterTable('is_visible', true)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_list_page_can_filter_by_rating(): void
    {
        $five = Review::factory()->create(['rating' => 5]);
        $one = Review::factory()->create(['rating' => 1]);

        Livewire::test(ListReviews::class)
            ->filterTable('rating', '5')
            ->assertCanSeeTableRecords([$five])
            ->assertCanNotSeeTableRecords([$one]);
    }

    public function test_list_page_can_search_by_hotel_name(): void
    {
        $hotel = PetHotel::factory()->create(['name' => 'Distinctive Kennels']);
        $match = Review::factory()->create(['hotel_id' => $hotel->id]);
        $other = Review::factory()->create();

        Livewire::test(ListReviews::class)
            ->searchTable('Distinctive Kennels')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    // ── Visibility toggle ─────────────────────────────────────────────────────

    public function test_toggle_visibility_action_hides_a_visible_review(): void
    {
        $review = Review::factory()->create();

        Livewire::test(ListReviews::class)
            ->callTableAction('toggleVisibility', $review)
            ->assertHasNoTableActionErrors();

        $this->assertFalse($review->fresh()->is_visible);
    }

    public function test_toggle_visibility_action_restores_a_hidden_review(): void
    {
        $review = Review::factory()->hidden()->create();

        Livewire::test(ListReviews::class)
            ->callTableAction('toggleVisibility', $review)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($review->fresh()->is_visible);
    }

    public function test_view_page_renders_a_review(): void
    {
        $review = Review::factory()->create();

        Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])
            ->assertSuccessful();
    }
}
