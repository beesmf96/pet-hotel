<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_headers_are_present_on_a_page_response(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * The middleware is registered globally rather than on the web group so the
     * Filament panels, which assemble their own stacks, are covered too.
     */
    public function test_headers_are_present_on_the_admin_panel(): void
    {
        $this->get('http://admin.pet-hotel.local/admin/login')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_hsts_is_omitted_over_plain_http(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_sent_over_https(): void
    {
        $this->get('https://web.pet-hotel.local/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
