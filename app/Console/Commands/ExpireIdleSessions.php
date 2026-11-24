<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Logs and removes sessions idle longer than the session lifetime (FR-11, wireframe 15:
 * "LOGOUT · auto logout after 15 min idle"). Runs every minute from the scheduler.
 */
class ExpireIdleSessions extends Command
{
    protected $signature = 'soc:expire-sessions';

    protected $description = 'Log and remove sessions idle longer than the session lifetime';

    public function handle(ActivityLogger $logger): int
    {
        $cutoff = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $idle = DB::table('sessions')
            ->where('last_activity', '<', $cutoff)
            ->get(['id', 'user_id', 'ip_address', 'last_activity']);

        $count = 0;

        foreach ($idle as $session) {
            // Delete only if still idle: a click in the last moment keeps the session alive.
            $deleted = DB::table('sessions')
                ->where('id', $session->id)
                ->where('last_activity', '<', $cutoff)
                ->delete();

            if ($deleted === 0) {
                continue;
            }

            $count++;

            if ($session->user_id && $user = User::find($session->user_id)) {
                $logger->log(AuditEvent::AUTH_LOGOUT, $user, [
                    'reason' => 'idle_timeout',
                    'ip' => $session->ip_address,
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity)->toDateTimeString(),
                ], $user);
            }
        }

        $this->info("Expired {$count} idle session(s).");

        return self::SUCCESS;
    }
}
