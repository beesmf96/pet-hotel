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

    // ── Content Security Policy ───────────────────────────────────────────────

    /**
     * Report-only is the default because an over-tight policy fails silently —
     * a blank map or a dead button, with no error page. This mode has the browser
     * report violations without acting on them.
     */
    public function test_csp_defaults_to_report_only(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('Content-Security-Policy');
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_csp_switches_to_enforcing_without_a_code_change(): void
    {
        config(['security.csp.mode' => 'enforce']);

        $this->get('/')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_csp_can_be_disabled(): void
    {
        config(['security.csp.mode' => 'off']);

        $this->get('/')
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_policy_allows_the_sources_the_app_actually_uses(): void
    {
        $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

        // Leaflet tiles in HotelMap.vue would silently blank the map without this.
        $this->assertStringContainsString('https://*.tile.openstreetmap.org', $policy);
        // Vue emits component styles as inline <style> blocks.
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString('report-uri /csp-report', $policy);
    }

    /**
     * With uploads on a bucket, every pet and hotel photo comes from an origin
     * 'self' does not cover.
     */
    public function test_an_off_origin_upload_bucket_is_allowed_as_an_image_source(): void
    {
        config([
            'filesystems.photos' => 's3',
            'filesystems.disks.s3.url' => 'https://pet-hotel-uploads.example.com/media',
        ]);

        $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString('https://pet-hotel-uploads.example.com', $policy);
    }

    public function test_the_bucket_endpoint_is_used_when_no_explicit_url_is_set(): void
    {
        config([
            'filesystems.photos' => 's3',
            'filesystems.disks.s3.url' => null,
            'filesystems.disks.s3.endpoint' => 'https://fra1.digitaloceanspaces.com',
        ]);

        $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString('https://fra1.digitaloceanspaces.com', $policy);
    }

    /**
     * The local "public" disk builds its URL from APP_URL, so it is already
     * covered by 'self' and must not be repeated into the policy.
     */
    public function test_a_same_origin_upload_disk_adds_nothing_to_the_policy(): void
    {
        config(['filesystems.photos' => 'public']);

        $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

        $imgSrc = collect(explode(';', $policy))
            ->map(fn (string $directive) => trim($directive))
            ->first(fn (string $d) => str_starts_with($d, 'img-src'));

        $this->assertSame("img-src 'self' data: blob: https://*.tile.openstreetmap.org", $imgSrc);
    }

    public function test_an_unconfigured_bucket_leaves_the_policy_alone(): void
    {
        config([
            'filesystems.photos' => 's3',
            'filesystems.disks.s3.url' => null,
            'filesystems.disks.s3.endpoint' => null,
        ]);

        $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("img-src 'self' data: blob:", $policy);
    }

    public function test_customer_facing_pages_do_not_allow_unsafe_script_sources(): void
    {
        $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');

        $scriptSrc = collect(explode(';', $policy))
            ->map(fn (string $directive) => trim($directive))
            ->first(fn (string $d) => str_starts_with($d, 'script-src'));

        $this->assertSame("script-src 'self'", $scriptSrc);
    }

    /**
     * Filament drives its UI with Alpine, which evaluates expressions at runtime.
     * The panels live on their own hostnames so this relaxation stays off the
     * customer-facing app rather than being applied globally.
     */
    public function test_filament_hosts_get_the_relaxation_alpine_requires(): void
    {
        $policy = $this->get('http://admin.pet-hotel.local/admin/login')
            ->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString("'unsafe-eval'", $policy);
    }
}
