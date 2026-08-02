<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
 * - A full Content-Security-Policy. Inertia + Vite need a policy worked out
 *   against the real asset pipeline; a wrong one silently breaks the SPA. That
 *   is tracked separately in .claude/plans/plan-owasp-hardening.md.
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

        return $response;
    }
}
