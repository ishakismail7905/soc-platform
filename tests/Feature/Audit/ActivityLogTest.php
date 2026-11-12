<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    public function test_log_records_actor_role_ip_and_links_the_chain(): void
    {
        $user = $this->actingAsRole(Role::SuperAdmin);

        $first = app(ActivityLogger::class)->log(AuditEvent::USER_CREATED, $user, ['k' => 'v']);
        $second = app(ActivityLogger::class)->log(AuditEvent::USER_UPDATED, $user);

        $this->assertSame($user->id, $first->user_id);
        $this->assertSame('super_admin', $first->role);
        $this->assertSame('USER_CREATED', $first->event);
        $this->assertNotNull($first->ip_address);
        $this->assertSame(str_repeat('0', 64), $first->prev_hash);
        $this->assertSame($first->hash, $second->prev_hash);
        $this->assertSame(64, strlen($second->hash));
    }

    public function test_the_app_cannot_update_or_delete_a_log_entry(): void
    {
        $row = app(ActivityLogger::class)->log(AuditEvent::AUTH_LOGIN);

        $this->expectException(\LogicException::class);
        $row->update(['event' => 'TAMPERED']);
    }

    public function test_the_database_rejects_raw_update_and_delete(): void
    {
        app(ActivityLogger::class)->log(AuditEvent::AUTH_LOGIN);

        try {
            DB::table('activity_logs')->update(['event' => 'TAMPERED']);
            $this->fail('UPDATE was allowed');
        } catch (QueryException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        $this->expectException(QueryException::class);
        DB::table('activity_logs')->delete();
    }

    public function test_verify_passes_on_a_clean_chain_and_catches_a_forged_row(): void
    {
        app(ActivityLogger::class)->log(AuditEvent::AUTH_LOGIN);
        app(ActivityLogger::class)->log(AuditEvent::AUTH_LOGOUT);

        $this->artisan('soc:audit-verify')
            ->expectsOutput('OK: 2 entries verified')
            ->assertExitCode(0);

        // An attacker with database access inserts a fake entry with a made-up hash.
        $lastHash = DB::table('activity_logs')->orderByDesc('id')->value('hash');
        $forgedId = DB::table('activity_logs')->insertGetId([
            'occurred_at' => now(), 'event' => 'LOGIN', 'properties' => '{}',
            'prev_hash' => $lastHash, 'hash' => str_repeat('f', 64),
        ]);

        $this->artisan('soc:audit-verify')
            ->expectsOutput("TAMPERED at id {$forgedId}")
            ->assertExitCode(1);
    }
}