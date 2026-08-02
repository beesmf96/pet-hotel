<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CspReportTest extends TestCase
{
    use RefreshDatabase;

    private function postReport(array $body): TestResponse
    {
        return $this->call(
            'POST', '/csp-report', [], [], [],
            ['CONTENT_TYPE' => 'application/csp-report'],
            json_encode($body),
        );
    }

    public function test_a_violation_report_is_logged(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'CSP violation'
                    && $context['directive'] === 'script-src'
                    && $context['blocked_uri'] === 'https://evil.example.com/x.js';
            });

        $this->postReport(['csp-report' => [
            'effective-directive' => 'script-src',
            'blocked-uri' => 'https://evil.example.com/x.js',
            'document-uri' => 'https://web.pet-hotel.local/hotels',
        ]])->assertNoContent();
    }

    /**
     * The endpoint is unauthenticated and world-writable by design — browsers
     * post to it with no session. Junk must not reach the log.
     */
    public function test_a_malformed_body_is_ignored_without_logging(): void
    {
        Log::shouldReceive('channel')->never();

        $this->postReport(['not-a-report' => 'junk'])->assertNoContent();

        $this->call(
            'POST', '/csp-report', [], [], [],
            ['CONTENT_TYPE' => 'application/csp-report'],
            'this is not json',
        )->assertNoContent();
    }

    public function test_the_endpoint_is_exempt_from_csrf(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning');

        // Would be a 419 if the CSRF exemption in bootstrap/app.php were missing.
        $this->postReport(['csp-report' => ['effective-directive' => 'img-src']])
            ->assertNoContent();
    }

    public function test_the_endpoint_is_rate_limited(): void
    {
        // Faked so 60 real reports do not land in storage/logs during a test run.
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning');

        foreach (range(1, 60) as $i) {
            $this->postReport(['csp-report' => ['effective-directive' => 'img-src']]);
        }

        $this->postReport(['csp-report' => ['effective-directive' => 'img-src']])
            ->assertStatus(429);
    }
}
