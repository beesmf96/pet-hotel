<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | mode:
    |   'report'  — send Content-Security-Policy-Report-Only. The browser checks
    |               the policy and reports what it *would* have blocked, but
    |               blocks nothing. Safe to run against real traffic.
    |   'enforce' — send Content-Security-Policy. Violations are blocked.
    |   'off'     — send no CSP header at all.
    |
    | Start on 'report', run it in production long enough to see real traffic,
    | fix whatever legitimate sources show up, then switch to 'enforce'. That
    | switch is an env change; no code has to move.
    |
    | report_uri:
    |   Where browsers POST violation reports. Leave empty and violations only
    |   surface in each visitor's devtools console — which nobody will read.
    |   Set it to '/csp-report' to collect them in the Laravel log instead.
    |
    */

    'csp' => [
        'mode' => env('CSP_MODE', 'report'),
        'report_uri' => env('CSP_REPORT_URI', '/csp-report'),
    ],

];
