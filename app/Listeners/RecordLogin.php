<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Auth\Events\Login;

class RecordLogin
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->saveQuietly();

        $this->logger->log(AuditEvent::AUTH_LOGIN, $event->user, [], $event->user);
    }
}