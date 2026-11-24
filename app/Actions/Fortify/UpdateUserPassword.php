<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\PasswordPolicy;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    public function __construct(private PasswordService $passwords) {}

    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => PasswordPolicy::rules($user),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $this->passwords->change($user, $input['password']);
    }
}
