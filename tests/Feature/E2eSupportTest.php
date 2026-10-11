<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\E2eSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use RuntimeException;
use Tests\TestCase;

/**
 * The app-side pieces of the Playwright suite in e2e/: the seeder that must
 * never run outside the e2e environment, and the Vite switch that makes the
 * e2e app serve built assets.
 */
class E2eSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_e2e_seeder_refuses_to_run_outside_the_e2e_environment(): void
    {
        $this->expectException(RuntimeException::class);

        $this->seed(E2eSeeder::class);
    }

    public function test_the_e2e_seeder_builds_the_fixed_hotel_and_accounts(): void
    {
        $this->app['env'] = 'e2e';

        $this->seed(E2eSeeder::class);

        $hotel = PetHotel::where('slug', 'e2e-paws')->sole();
        $this->assertSame(2, $hotel->capacity);
        $this->assertSame(['cat', 'dog'], $hotel->pricing()->orderBy('pet_type')->pluck('pet_type')->all());
        $this->assertTrue($hotel->owners()->where('email', 'owner@e2e.test')->exists());
        $this->assertTrue(User::where('email', 'admin@e2e.test')->sole()->is_admin);
        $this->assertSame(2, User::where('email', 'alice@e2e.test')->sole()->pets()->count());
    }

    public function test_the_e2e_environment_serves_built_assets(): void
    {
        $this->app['env'] = 'e2e';

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame(storage_path('framework/e2e-never-hot'), Vite::hotFile());
        $this->assertFalse(Vite::isRunningHot());
    }

    public function test_other_environments_keep_the_default_hot_file(): void
    {
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame(public_path('hot'), Vite::hotFile());
    }
}
