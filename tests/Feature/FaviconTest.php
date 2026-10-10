<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The icon ships as three files: an SVG for modern browsers, a multi-size ICO
 * fallback, and a square PNG for the iOS home screen. These lock both halves —
 * the files are really there and really icons, and every entry point links them.
 */
class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public static function iconFiles(): array
    {
        return [
            'svg' => ['favicon.svg'],
            'ico' => ['favicon.ico'],
            'apple touch icon' => ['apple-touch-icon.png'],
        ];
    }

    #[DataProvider('iconFiles')]
    public function test_the_icon_file_exists_and_is_not_empty(string $file): void
    {
        $path = public_path($file);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path), "{$file} is empty");
    }

    public function test_the_ico_carries_three_sizes(): void
    {
        $bytes = file_get_contents(public_path('favicon.ico'));

        // ICONDIR: two reserved bytes, type 1 (icon), then the image count.
        [$reserved, $type, $count] = array_values(unpack('vreserved/vtype/vcount', $bytes));

        $this->assertSame(0, $reserved);
        $this->assertSame(1, $type);
        $this->assertSame(3, $count);

        $sizes = [];
        for ($i = 0; $i < $count; $i++) {
            $entry = unpack('Cwidth/Cheight', substr($bytes, 6 + 16 * $i, 2));
            $sizes[] = $entry['width'];
        }

        $this->assertSame([16, 32, 48], $sizes);
    }

    public function test_the_svg_is_an_svg(): void
    {
        $svg = file_get_contents(public_path('favicon.svg'));

        $this->assertStringContainsString('<svg', $svg);
        // The palette must stay in step with resources/css/tokens.css.
        $this->assertStringContainsString('#0f766e', $svg);
        $this->assertStringContainsString('#fff8e7', $svg);
    }

    public function test_a_customer_page_links_every_icon(): void
    {
        $response = $this->get('/')->assertSuccessful();

        $response->assertSee('rel="icon" href="'.asset('favicon.ico').'" sizes="32x32"', false);
        $response->assertSee('rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml"', false);
        $response->assertSee('rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'"', false);
    }

    public static function panels(): array
    {
        return [
            'admin' => ['admin'],
            'hotel owner' => ['hotel-owner'],
        ];
    }

    #[DataProvider('panels')]
    public function test_the_panel_sets_the_favicon(string $id): void
    {
        $this->assertSame(asset('favicon.svg'), Filament::getPanel($id)->getFavicon());
    }

    public function test_the_admin_login_page_links_the_favicon(): void
    {
        $this->get('/admin/login')
            ->assertSuccessful()
            ->assertSee('<link rel="icon" href="'.asset('favicon.svg').'" />', false);
    }
}
