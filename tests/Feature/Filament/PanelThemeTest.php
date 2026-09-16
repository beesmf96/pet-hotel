<?php

namespace Tests\Feature\Filament;

use App\Models\PetHotel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Both panels share one Vite theme and one brand so they match the customer
 * pages. These lock the wiring: the theme path, the brand, no dark mode, and
 * the theme stylesheet actually reaching the page.
 */
class PanelThemeTest extends TestCase
{
    use RefreshDatabase;

    public static function panels(): array
    {
        return [
            'admin' => ['admin'],
            'hotel owner' => ['hotel-owner'],
        ];
    }

    #[DataProvider('panels')]
    public function test_the_panel_uses_the_shared_vite_theme(string $id): void
    {
        $this->assertSame('resources/css/filament/theme.css', Filament::getPanel($id)->getViteTheme());
    }

    #[DataProvider('panels')]
    public function test_the_panel_is_branded_and_light_only(string $id): void
    {
        $panel = Filament::getPanel($id);

        $this->assertSame('PetHotel', $panel->getBrandName());
        $this->assertStringContainsString('PetHotel', (string) $panel->getBrandLogo());
        $this->assertFalse($panel->hasDarkMode());
    }

    public function test_the_admin_login_page_links_the_theme_stylesheet(): void
    {
        $this->get('/admin/login')
            ->assertSuccessful()
            ->assertSee('theme', false);
    }

    public function test_the_owner_bookings_page_renders_the_brand_partial(): void
    {
        $hotel = PetHotel::factory()->create();
        $this->actingAs(User::factory()->hotelOwner($hotel)->create());

        $this->get('/owner/bookings')
            ->assertSuccessful()
            ->assertSee('PetHotel Owner');
    }
}
