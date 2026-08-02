<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Collects Content-Security-Policy violation reports.
 *
 * Browsers POST here on their own; this is not called by our frontend. It is
 * therefore an unauthenticated public endpoint that anyone can post junk to,
 * which is why the route is throttled and only a fixed set of fields is logged.
 *
 * Exists so the report-only phase produces evidence somewhere we will actually
 * look. Without a report-uri the violations land in each individual visitor's
 * devtools console and are lost.
 */
class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Browsers send application/csp-report, which Laravel does not parse as
        // JSON, so the body is decoded by hand.
        $payload = json_decode($request->getContent(), true);
        $report = $payload['csp-report'] ?? null;

        if (! is_array($report)) {
            return response()->noContent();
        }

        Log::channel(config('logging.default'))->warning('CSP violation', [
            'directive' => $report['effective-directive'] ?? $report['violated-directive'] ?? null,
            'blocked_uri' => $report['blocked-uri'] ?? null,
            'document_uri' => $report['document-uri'] ?? null,
            'source_file' => $report['source-file'] ?? null,
            'line_number' => $report['line-number'] ?? null,
        ]);

        return response()->noContent();
    }
}
