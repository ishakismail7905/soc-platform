<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when an account locks after too many failed logins (FR-11).
 * Sprint 2 turns this into a security alert in the alert queue.
 */
class AccountLockedOut
{
    use Dispatchable;

    public function __construct(public User $user, public ?string $ip) {}
}
