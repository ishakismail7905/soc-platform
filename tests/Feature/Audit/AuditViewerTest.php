<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditViewerTest extends TestCase
{
    private function log(string $event, $subject = null, array $properties = [], ?User $actor = null): ActivityLog
    {
        return app(ActivityLogger::class)->log($event, $subject, $properties, $actor);
    }

    public function test_only_super_admin_and_auditor_can_view(): void
    {
        $this->actingAsRole(Role::Auditor);
        $this->get('/audit')->assertOk()
            ->assertSee('Read-only for every role. Entries are append-only and cannot be edited or deleted.');

        $this->actingAsRole(Role::SuperAdmin);
        $this->get('/audit')->assertOk();

        $this->actingAsRole(Role::ItManager);
        $this->get('/audit')->assertForbidden();

        $this->actingAsRole(Role::SocAnalyst, [Client::factory()->create()]);
        $this->get('/audit')->assertForbidden();
    }

    public function test_filter_by_event(): void
    {
        $this->log(AuditEvent::AUTH_LOGIN);
        $this->log(AuditEvent::USER_CREATED);

        $this->actingAsRole(Role::Auditor);
        $this->get('/audit?event=USER_CREATED')
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('event')->all() === ['USER_CREATED']);
    }

    public function test_filter_by_user_includes_events_about_them(): void
    {
        $admin = User::factory()->withRole(Role::SuperAdmin)->create();
        $hafiz = User::factory()->withRole(Role::SocAnalyst)->create();
        $other = User::factory()->withRole(Role::SocAnalyst)->create();

        $this->log(AuditEvent::USER_CREATED, $hafiz, [], $admin);     // done TO Hafiz
        $this->log(AuditEvent::AUTH_FAILED, $hafiz, ['reason' => 'x']); // against Hafiz, before sign-in
        $this->log(AuditEvent::AUTH_LOGIN, $other, [], $other);        // unrelated

        $this->actingAsRole(Role::Auditor);
        $this->get('/audit?user_id='.$hafiz->id)
            ->assertViewHas('logs', fn ($logs) => $logs->count() === 2);
    }

    public function test_filter_by_dates_and_reject_a_backwards_range(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 10:00'));
        $this->log(AuditEvent::AUTH_LOGIN);
        $this->travelTo(Carbon::parse('2026-11-20 10:00'));
        $this->log(AuditEvent::AUTH_LOGOUT);

        $this->actingAsRole(Role::Auditor);
        $this->get('/audit?from=2026-11-15')
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('event')->all() === ['LOGOUT']);
        $this->get('/audit?from=2026-11-20&to=2026-11-01')->assertSessionHasErrors('to');
    }

    public function test_pre_login_rows_show_the_target_account_and_its_role(): void
    {
        $hafiz = User::factory()->withRole(Role::SocAnalyst)->create(['name' => 'Hafiz M.']);
        $this->post('/login', ['email' => $hafiz->email, 'password' => 'wrong-password-123']);

        $row = ActivityLog::where('event', AuditEvent::AUTH_FAILED)->sole();
        $this->assertNull($row->user_id); // nobody was signed in
        $this->assertSame('soc_analyst', $row->properties['target_role']);

        $this->actingAsRole(Role::Auditor);
        $this->get('/audit')->assertSee('Hafiz M.')->assertSee('(target)')->assertSee('SOC Analyst');
    }

    public function test_attacker_text_in_the_log_is_shown_as_text_not_run_as_code(): void
    {
        // Stored XSS against the auditor: an attacker types a script as the login email.
        $this->post('/login', ['email' => '<script>alert(1)</script>', 'password' => 'x']);

        $this->actingAsRole(Role::Auditor);
        $this->get('/audit')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_export_is_logged_first_and_formula_safe(): void
    {
        // "=" is legal in an email address, so a formula can arrive through a real field.
        $sneaky = User::factory()->withRole(Role::SocAnalyst)->create(['email' => '=SUM(1+1)@demo.test']);
        $this->log(AuditEvent::AUTH_LOGIN, $sneaky, [], $sneaky);

        $this->actingAsRole(Role::Auditor);
        $response = $this->get('/audit/export');

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith('id,occurred_at,user_email,role,client,event,subject_type,subject_id,ip_address,properties,hash', $csv);
        $this->assertStringContainsString("'=SUM(1+1)@demo.test", $csv);
        $this->assertStringNotContainsString(',=SUM', $csv);

        $export = ActivityLog::where('event', AuditEvent::AUDIT_EXPORTED)->sole();
        $this->assertSame(1, $export->properties['row_count']);
        $this->assertCount(2, explode("\n", trim($csv))); // header + exactly the 1 counted row
    }

    public function test_export_needs_the_export_permission(): void
    {
        $this->actingAsRole(Role::ItManager);

        $this->get('/audit/export')->assertForbidden();
        $this->assertSame(0, ActivityLog::where('event', AuditEvent::AUDIT_EXPORTED)->count());
    }

    public function test_no_route_can_change_the_log(): void
    {
        $this->assertTrue(Route::has('audit.index'));

        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'audit')) {
                $this->assertSame([], array_diff($route->methods(), ['GET', 'HEAD']), $route->uri());
            }
        }
    }
}