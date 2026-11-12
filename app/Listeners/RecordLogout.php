<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->logger->log(AuditEvent::AUTH_LOGOUT, $event->user, [], $event->user);
        }
    }
}