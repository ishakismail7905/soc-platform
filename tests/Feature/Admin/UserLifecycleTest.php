<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Exceptions\UserAdminException;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Services\UserAdminService;
use App\Support\AuditEvent;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLifecycleTest extends TestCase
{
    /** Pretend the user is signed in somewhere else (a row in the sessions table). */
    private function sessionFor(User $user, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id, 'ip_address' => '192.0.2.20',
            'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
        ]);
    }

    public function test_edit_panel_matches_wireframe_14(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $user = User::factory()->withRole(Role::SocAnalyst)->twoFactorConfirmed()->create(['name' => 'Aiman R.']);

        $this->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Edit account: Aiman R.')
            ->assertSee('Reset 2FA')
            ->assertSee('Force reset at next login')
            ->assertSee('Role, client and status changes are logged as permission changes.');
    }

    public function test_role_change_is_logged_with_from_and_to(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $user = User::factory()->withRole(Role::Auditor)->create();

        $this->put(route('admin.users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => 'it_manager'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(Role::ItManager, $user->fresh()->role);
        $log = ActivityLog::where('event', AuditEvent::USER_ROLE_CHANGED)->sole();
        $this->assertSame($user->id, $log->subject_id);
        $this->assertSame(['field' => 'role', 'from' => 'auditor', 'to' => 'it_manager'], $log->properties);
        $this->assertSame(0, ActivityLog::where('event', AuditEvent::USER_UPDATED)->count()); // nothing else changed
    }

    public function test_client_change_is_logged_with_from_and_to(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        [$a, $b] = Client::factory()->count(2)->create()->all();
        $user = User::factory()->withRole(Role::SocAnalyst)->forClients($a)->create();

        $this->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'soc_analyst', 'client_ids' => [$b->id, $a->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $user->fresh()->clients->pluck('id')->all());
        $log = ActivityLog::where('event', AuditEvent::USER_ROLE_CHANGED)->sole();
        $this->assertSame('clients', $log->properties['field']);
        $this->assertSame([$a->id], $log->properties['from']);
        $this->assertSame([$a->id, $b->id], $log->properties['to']);
    }

    public function test_deactivation_ends_sessions_and_reactivation_restores_access(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $user = User::factory()->withRole(Role::SocAnalyst)->create();
        $this->sessionFor($user, 'live-session');

        $this->post(route('admin.users.deactivate', $user))->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::USER_DEACTIVATED, 'subject_id' => $user->id]);

        $this->post(route('admin.users.reactivate', $user))->assertRedirect();
        $this->assertTrue($user->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::USER_REACTIVATED, 'subject_id' => $user->id]);
    }

    public function test_super_admin_cannot_deactivate_or_demote_themself(): void
    {
        $me = $this->actingAsRole(Role::SuperAdmin);
        User::factory()->withRole(Role::SuperAdmin)->create(); // another admin exists, still refused

        $this->post(route('admin.users.deactivate', $me))->assertSessionHas('error');
        $this->put(route('admin.users.update', $me), ['name' => $me->name, 'email' => $me->email, 'role' => 'it_manager'])
            ->assertSessionHas('error');

        $me->refresh();
        $this->assertTrue($me->is_active);
        $this->assertSame(Role::SuperAdmin, $me->role);
    }

    public function test_the_last_usable_super_admin_can_never_be_removed(): void
    {
        $only = User::factory()->withRole(Role::SuperAdmin)->create();
        User::factory()->withRole(Role::SuperAdmin)->create(['locked_at' => now()]); // locked: can't sign in to fix anything
        $actor = User::factory()->withRole(Role::ItManager)->create();

        $this->expectException(UserAdminException::class);
        $this->expectExceptionMessage('At least one active Super Admin must remain.');

        app(UserAdminService::class)->deactivate($only, $actor);
    }

    public function test_unlock_clears_the_lock_and_the_counter(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $user = User::factory()->withRole(Role::SocAnalyst)->create(['locked_at' => now(), 'failed_login_attempts' => 5]);

        $this->post(route('admin.users.unlock', $user))->assertRedirect();

        $this->assertNull($user->fresh()->locked_at);
        $this->assertSame(0, $user->fresh()->failed_login_attempts);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::USER_UNLOCKED, 'subject_id' => $user->id]);
    }

    public function test_force_password_reset_issues_a_new_temporary_password_and_ends_sessions(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $user = User::factory()->withRole(Role::SocAnalyst)->create();
        $this->sessionFor($user, 'old-session');

        $this->post(route('admin.users.reset-password', $user))->assertSessionHas('temporary_password');

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check(session('temporary_password'), $user->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::USER_PASSWORD_RESET, 'subject_id' => $user->id]);
    }

    public function test_reset_two_factor_forces_re_enrolment_and_ends_sessions(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $user = User::factory()->withRole(Role::SocAnalyst)->twoFactorConfirmed()->create();
        $this->sessionFor($user, 'phone-lost');

        $this->post(route('admin.users.reset-two-factor', $user))->assertRedirect();

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::USER_2FA_RESET, 'subject_id' => $user->id]);
    }

    public function test_non_admins_cannot_use_any_account_action(): void
    {
        $this->actingAsRole(Role::ItManager);
        $target = User::factory()->withRole(Role::SuperAdmin)->create(['locked_at' => now()]);

        foreach (['deactivate', 'reactivate', 'unlock', 'reset-password', 'reset-two-factor'] as $action) {
            $this->post(route("admin.users.{$action}", $target))->assertForbidden();
        }
        $this->put(route('admin.users.update', $target), ['name' => 'x', 'email' => $target->email, 'role' => 'auditor'])
            ->assertForbidden();
        $this->get(route('admin.users.edit', $target))->assertForbidden();

        $this->assertNotNull($target->fresh()->locked_at);
        $this->assertSame(Role::SuperAdmin, $target->fresh()->role);
    }

    public function test_demo_seeder_creates_fictional_accounts_that_must_change_their_password(): void
    {
        config(['soc.seed_password' => 'demo-seed-passphrase-2026']);

        app(DemoSeeder::class)->run();
        app(DemoSeeder::class)->run(); // running it twice changes nothing

        $this->assertSame(['CLA', 'CLB', 'CLC'], Client::orderBy('code')->pluck('code')->all());

        $demo = User::where('email', 'like', '%@demo.test')->get();
        $this->assertCount(7, $demo);
        $this->assertTrue($demo->every(fn (User $u) => $u->must_change_password));
        $this->assertSame(
            collect(Role::cases())->map->value->sort()->values()->all(),
            $demo->map(fn (User $u) => $u->role->value)->unique()->sort()->values()->all()
        );
        $aiman = $demo->firstWhere('email', 'aiman@demo.test');
        $this->assertEqualsCanonicalizing(['CLA', 'CLC'], $aiman->clients->pluck('code')->all());
    }

    public function test_demo_seeder_refuses_a_weak_or_missing_seed_password(): void
    {
        config(['soc.seed_password' => 'short']);

        $this->expectException(\RuntimeException::class);

        app(DemoSeeder::class)->run();
    }
}