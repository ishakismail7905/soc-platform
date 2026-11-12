<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Checks email + password for Fortify. Returning null = login refused. */
class AuthenticateUser
{
    public function __construct(private ActivityLogger $logger) {}

    public function __invoke(Request $request): ?User
    {
        $email = Str::lower((string) $request->input('email'));
        $password = (string) $request->input('password');
        $user = User::where('email', $email)->first();

        if ($user === null) {
            // Spend the same time as a real password check, so response time
            // doesn't reveal which emails exist (timing-based enumeration).
            Hash::make($password);
            $this->logger->log(AuditEvent::AUTH_FAILED, null, ['email' => $email]);

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            $this->logger->log(AuditEvent::AUTH_FAILED, $user, ['email' => $email]);

            return null;
        }

        return $user;
    }
}