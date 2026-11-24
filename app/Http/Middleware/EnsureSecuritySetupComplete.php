<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every app page until the account is safe to use (FR-11, FR-12):
 * 1. a temporary password from the Super Admin has been changed;
 * 2. 2FA is confirmed for every role that requires it.
 */
class EnsureSecuritySetupComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request); // 'auth' runs before this; nothing to check for guests
        }

        if ($user->must_change_password) {
            return redirect()->route('security.edit')
                ->with('warning', 'Change your temporary password before you continue.');
        }

        // Fail closed: a user with no role is treated as needing 2FA.
        $needsTwoFactor = $user->role?->requiresTwoFactor() ?? true;

        if ($needsTwoFactor && $user->two_factor_confirmed_at === null) {
            return redirect()->route('security.edit')
                ->with('warning', 'Set up two-factor authentication before you continue. Your role requires it.');
        }

        return $next($request);
    }
}
