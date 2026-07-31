<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_belongs_to_user(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $review->user());
        $this->assertEquals($review->user_id, $review->user->id);
    }

    public function test_review_belongs_to_hotel(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $review->hotel());
        $this->assertEquals($review->hotel_id, $review->hotel->id);
    }

    public function test_review_belongs_to_booking(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $review->booking());
        $this->assertEquals($review->booking_id, $review->booking->id);
    }

    public function test_review_is_visible_by_default(): void
    {
        $review = Review::factory()->create();

        $this->assertTrue($review->is_visible);
    }

    public function test_review_rating_is_cast_to_integer(): void
    {
        $review = Review::factory()->create(['rating' => '4']);

        $this->assertSame(4, $review->fresh()->rating);
    }
}
