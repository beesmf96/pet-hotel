<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response hardening.
 *
 * These live here rather than in docker/nginx/conf.d/* so they apply on any
 * deployment target, not only the bundled compose stack.
 *
 * Deliberately not set here:
 *
 * - HSTS, which is emitted only over HTTPS (see below). Sending it over plain
 *   HTTP is ignored by browsers anyway, and committing to it before TLS is
 *   confirmed working is painful to walk back — the max-age sticks in browsers.
 *
 * The Content-Security-Policy defaults to report-only mode; see config/security.php.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Clickjacking. The Filament admin and hotel-owner panels are the real
        // targets here — framing them lets an attacker proxy privileged actions.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Stop the browser second-guessing declared content types, which is how
        // a user-uploaded pet photo gets interpreted as script.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Keep full URLs (booking ids, reset tokens) out of cross-origin referers.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($header = $this->cspHeaderName()) {
            $response->headers->set($header, $this->contentSecurityPolicy($request));
        }

        return $response;
    }

    /**
     * Report-Only tells the browser to check the policy and report violations
     * without blocking anything, so a wrong policy cannot break the site.
     */
    private function cspHeaderName(): ?string
    {
        return match (config('security.csp.mode')) {
            'enforce' => 'Content-Security-Policy',
            'report' => 'Content-Security-Policy-Report-Only',
            default => null,
        };
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $directives = [
            'default-src' => ["'self'"],

            // Blocks <base href> hijacking and restricts where forms may post,
            // both of which survive an otherwise-contained script injection.
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],

            // Same intent as the X-Frame-Options header above; CSP is the modern
            // spelling and both are sent for older browsers.
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],

            // data: covers inline SVG icons; blob: covers client-side previews of
            // a pet photo before upload. The OSM host serves Leaflet map tiles —
            // see HotelMap.vue.
            'img-src' => ["'self'", 'data:', 'blob:', 'https://*.tile.openstreetmap.org'],
            'font-src' => ["'self'", 'data:'],

            // Vue injects component styles as inline <style> blocks, so this
            // cannot be tightened without moving to nonces or hashes.
            'style-src' => ["'self'", "'unsafe-inline'"],

            'script-src' => ["'self'"],
            'connect-src' => ["'self'"],
        ];

        // Filament drives its UI with Alpine, which evaluates expression strings
        // at runtime — that is exactly what 'unsafe-eval' permits. The panels sit
        // under their own path prefixes, so the customer-facing SPA keeps the
        // tighter policy instead of inheriting this relaxation.
        if ($this->isFilamentRequest($request)) {
            $directives['script-src'][] = "'unsafe-eval'";
            $directives['script-src'][] = "'unsafe-inline'";
        }

        // With `bun run dev`, assets and the hot-reload websocket come from the
        // Vite dev server rather than this origin. Without these the policy would
        // report a flood of violations that say nothing about production.
        //
        // Gated on the local environment as well as the hot file: public/hot is
        // build output, and a stale one shipped in a deploy artifact would
        // otherwise quietly widen the live policy to include 'unsafe-inline' and
        // a localhost origin.
        if (app()->environment('local') && Vite::isRunningHot() && ($origin = $this->viteDevServerOrigin())) {
            $directives['script-src'][] = $origin;
            $directives['script-src'][] = "'unsafe-inline'";
            $directives['style-src'][] = $origin;
            $directives['connect-src'][] = $origin;
            $directives['connect-src'][] = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $origin);
        }

        // The Filament and Vite branches above can both contribute 'unsafe-inline'.
        $policy = collect($directives)
            ->map(fn (array $sources, string $name) => $name.' '.implode(' ', array_unique($sources)))
            ->values()
            ->all();

        if ($reportUri = config('security.csp.report_uri')) {
            $policy[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $policy);
    }

    /**
     * Both panels are routed by path on the app's single domain, so this cannot
     * key off the hostname. The domain check is still honoured for the day a
     * custom domain makes subdomain panels worthwhile again.
     *
     * Matching is anchored rather than a substring test: a hotel slug such as
     * /hotels/admin-kennels must not pull the panel relaxation onto a
     * customer-facing page.
     */
    private function isFilamentRequest(Request $request): bool
    {
        return collect(Filament::getPanels())
            ->contains(function (Panel $panel) use ($request): bool {
                $domains = $panel->getDomains();

                if ($domains !== [] && ! in_array($request->getHost(), $domains, true)) {
                    return false;
                }

                $path = trim($panel->getPath(), '/');

                return $path === '' || $request->is($path, $path.'/*');
            });
    }

    /**
     * The hot file holds the dev server URL Vite is actually serving from, which
     * is not always the documented default once ports collide.
     */
    private function viteDevServerOrigin(): ?string
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return null;
        }

        $url = trim((string) file_get_contents($hotFile));
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
