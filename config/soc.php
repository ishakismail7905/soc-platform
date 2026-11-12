<?php

// Security parameters for the Ardia SOC system.
// Sources: Chapter 3 Table 3.1 (FR11) and client interview Q12.
return [

    'password' => [
        // FR11: minimum 12 characters.
        'min_length' => 12,
        // FR11 / Q12: mixed character types. NIST SP 800-63B-4 advises
        // against composition rules, so this can be switched off.
        'require_mixed_types' => (bool) env('SOC_PASSWORD_MIXED_TYPES', false),
        // FR11: no reuse of recent passwords (depth chosen in plan, open decision 3).
        'history' => 5,
    ],

    'lockout' => [
        // FR11: lock the account after 5 failed attempts.
        'max_attempts' => 5,
    ],

    // NFR1: Content Security Policy header. Off locally for the Vite dev server.
    'csp_enabled' => (bool) env('SOC_CSP_ENABLED', true),

];