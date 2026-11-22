<?php

namespace App\Support;

/**
 * Activity-log event codes. Values match wireframe 15 (Activity Log).
 */
final class AuditEvent
{
    public const AUTH_LOGIN = 'LOGIN';
    public const AUTH_LOGOUT = 'LOGOUT';
    public const AUTH_FAILED = 'LOGIN_FAILED';
    public const AUTH_LOCKOUT = 'ACCOUNT_LOCKED';
    public const AUTH_SESSION_TERMINATED = 'SESSION_TERMINATED';
    public const AUTH_PASSWORD_CHANGED = 'PASSWORD_CHANGED';
    public const AUTH_2FA_ENABLED = 'TWO_FACTOR_ENABLED';
    public const AUTH_2FA_DISABLED = 'TWO_FACTOR_DISABLED';
    public const AUTH_2FA_FAILED = 'TWO_FACTOR_FAILED';
    public const AUTH_RECOVERY_CODE_USED = 'RECOVERY_CODE_USED';

    public const USER_CREATED = 'USER_CREATED';
    public const USER_UPDATED = 'USER_UPDATED';
    public const USER_ROLE_CHANGED = 'PERMISSION_CHANGE';
    public const USER_DEACTIVATED = 'USER_DEACTIVATED';
    public const USER_REACTIVATED = 'USER_REACTIVATED';
    public const USER_UNLOCKED = 'ACCOUNT_UNLOCKED';
    public const USER_PASSWORD_RESET = 'PASSWORD_RESET_FORCED';
    public const USER_2FA_RESET = 'TWO_FACTOR_RESET';

    public const CLIENT_CREATED = 'CLIENT_CREATED';
    public const CLIENT_UPDATED = 'CLIENT_UPDATED';
    public const CLIENT_DEACTIVATED = 'CLIENT_DEACTIVATED';
    public const CLIENT_REACTIVATED = 'CLIENT_REACTIVATED';

    public const ACCESS_DENIED = 'ACCESS_DENIED';
    public const AUDIT_EXPORTED = 'EXPORT';

    /** Every event code, for the activity-log filter and its validation. @return list<string> */
    public static function all(): array
    {
        $codes = array_values(array_unique((new \ReflectionClass(self::class))->getConstants()));
        sort($codes);

        return $codes;
    }
}