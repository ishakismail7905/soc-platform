<?php

namespace App\Services;

use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\DB;

/** The only place a password is changed: hashes it, records history, logs it. */
class PasswordService
{
    public function __construct(private ActivityLogger $logger) {}

    public function change(User $user, string $plain): void
    {
        DB::transaction(function () use ($user, $plain) {
            // The 'hashed' cast turns the plain text into a bcrypt hash on assignment.
            $user->forceFill([
                'password' => $plain,
                'password_changed_at' => now(),
                'must_change_password' => false,
            ])->save();

            $user->passwordHistories()->create(['password' => $user->password]);

            $this->logger->log(AuditEvent::AUTH_PASSWORD_CHANGED, $user, [], $user);
        });
    }
}
