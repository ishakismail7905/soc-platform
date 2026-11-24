<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        // After "Confirm password", Fortify sends the user to the intended URL: bring them back here.
        redirect()->setIntendedUrl(route('security.edit'));

        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        return view('security.edit', [
            'user' => $user,
            'passwordConfirmed' => time() - $confirmedAt < (int) config('auth.password_timeout', 10800),
            'twoFactorOn' => $user->two_factor_confirmed_at !== null,
            'twoFactorPending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'roleRequiresTwoFactor' => $user->role?->requiresTwoFactor() ?? true,
            // Fortify flashes this status once, right after confirmation, so the codes appear once.
            'showRecoveryCodes' => session('status') === 'two-factor-authentication-confirmed',
        ]);
    }
}
