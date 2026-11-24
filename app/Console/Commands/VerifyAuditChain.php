<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAuditChain extends Command
{
    protected $signature = 'soc:audit-verify';

    protected $description = 'Recompute the activity-log hash chain and report the first tampered entry';

    public function handle(ActivityLogger $logger): int
    {
        $prev = ActivityLogger::GENESIS_HASH;
        $count = 0;

        // Raw rows (no model casts), read in batches of 500 to keep memory low.
        foreach (DB::table('activity_logs')->lazyById(500) as $row) {
            $expected = $logger->hashRow($prev, (array) $row);

            if ($row->prev_hash !== $prev || ! hash_equals($expected, $row->hash)) {
                $this->error("TAMPERED at id {$row->id}");

                return self::FAILURE;
            }

            $prev = $row->hash;
            $count++;
        }

        $this->info("OK: {$count} entries verified");

        return self::SUCCESS;
    }
}
