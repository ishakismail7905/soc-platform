<?php

namespace App\Exceptions;

use RuntimeException;

/** A user-management action the rules forbid. Shown to the Super Admin as a plain message. */
class UserAdminException extends RuntimeException
{
    public static function lastSuperAdmin(): self
    {
        return new self('At least one active Super Admin must remain.');
    }

    public static function ownAccount(): self
    {
        return new self("You can't change your own role, status, password or 2FA here. Ask another Super Admin, or use Account security to change your password.");
    }
}