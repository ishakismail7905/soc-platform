<?php

namespace App\Actions\Auth;

use App\Events\AccountLockedOut;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Checks email + password for Fortify (FR-11).
 * Returning null = "Invalid email or password."; throwing = "This account is locked…".
 */
class AuthenticateUser
{
    public function __construct(private ActivityLogger $logger) {}

    public function __invoke(Request $request): ?User
    {
        $email = Str::lower((string) $request->input('email'));
        $password = (string) $request->input('password');
        $maxAttempts = (int) config('soc.lockout.max_attempts');
        $user = User::where('email', $email)->first();

        // Unknown email: behave exactly like a real account, including the lockout message,
        // so the login page can't be used to discover which emails exist.
        if ($user === null) {
            Hash::make($password); // same time cost as a real check
            $this->logger->log(AuditEvent::AUTH_FAILED, null, ['email' => $email, 'reason' => 'unknown']);

            // Key is a hash, so the cache never stores the typed email itself.
            $key = 'login-fail:' . hash('sha256', $email);
            Cache::add($key, 0, now()->addDay());

            if (Cache::increment($key) >= $maxAttempts) {
                $this->locked();
            }

            return null;
        }

        // Locked: refused even with the right password, until a Super Admin unlocks it.
        if ($user->locked_at !== null) {
            Hash::check($password, $user->password);
            $this->logger->log(AuditEvent::AUTH_FAILED, $user, ['email' => $email, 'reason' => 'locked']);
            $this->locked();
        }

        // Deactivated: generic message, so the account's status isn't revealed.
        if (! $user->is_active) {
            Hash::check($password, $user->password);
            $this->logger->log(AuditEvent::AUTH_FAILED, $user, ['email' => $email, 'reason' => 'inactive']);

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            // Atomic +1 in the database, so two simultaneous attempts can't both read "4".
            User::whereKey($user->id)->increment('failed_login_attempts');
            $attempts = (int) $user->fresh()->failed_login_attempts;

            $this->logger->log(AuditEvent::AUTH_FAILED, $user, ['email' => $email, 'attempt' => $attempts]);

            if ($attempts >= $maxAttempts) {
                $user->forceFill(['locked_at' => now()])->saveQuietly();
                $this->logger->log(AuditEvent::AUTH_LOCKOUT, $user, ['attempts' => $attempts]);
                AccountLockedOut::dispatch($user, $request->ip());
                $this->locked();
            }

            return null;
        }

        if ($user->failed_login_attempts > 0) {
            $user->forceFill(['failed_login_attempts' => 0])->saveQuietly();
        }

        return $user;
    }

    private function locked(): never
    {
        throw ValidationException::withMessages(['email' => __('auth.locked')]);
    }
}