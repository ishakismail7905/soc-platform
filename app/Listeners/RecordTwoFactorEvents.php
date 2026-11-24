<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Laravel finds each handle* method automatically and listens for the event
 * named in its type hint, so nothing needs registering in a provider.
 */
class RecordTwoFactorEvents
{
    public function __construct(private ActivityLogger $logger) {}

    public function handleConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->logger->log(AuditEvent::AUTH_2FA_ENABLED, $event->user);
    }

    public function handleDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->logger->log(AuditEvent::AUTH_2FA_DISABLED, $event->user);
    }

    /** Right password, wrong code: someone may have the password but not the phone. */
    public function handleFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->logger->log(AuditEvent::AUTH_2FA_FAILED, $event->user);
    }

    public function handleRecoveryCodeUsed(RecoveryCodeReplaced $event): void
    {
        // Never log the code itself: it is a credential.
        $this->logger->log(AuditEvent::AUTH_RECOVERY_CODE_USED, $event->user);
    }
}
