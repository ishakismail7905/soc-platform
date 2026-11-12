<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

/** FR-11 password rules in one place, used everywhere a password is set. */
final class PasswordPolicy
{
    public static function rules(?User $user = null): array
    {
        $rules = [
            'required', 'string',
            'min:' . config('soc.password.min_length'), // 12 (FR-11)
            'max:64',                                    // NIST SP 800-63B-4 minimum maximum; stays under bcrypt's 72-byte limit
            'confirmed',
        ];

        // Off by default (report v7 follows NIST); kept as a switch.
        if (config('soc.password.require_mixed_types')) {
            $rules[] = Password::min((int) config('soc.password.min_length'))->mixedCase()->numbers()->symbols();
        }

        $rules[] = new NotRecentlyUsed($user, (int) config('soc.password.history'));

        return $rules;
    }
}