<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionEndTest extends TestCase
{
    private const MARKER = 'soc_was_signed_in';

    public function test_expired_session_shows_session_ended(): void
    {
        // Browser still has the "was signed in" marker but no valid session.
        $this->withCookie(self::MARKER, '1')
            ->get('/dashboard')
            ->assertRedirect('/login')
            ->assertCookieExpired(self::MARKER);

        $this->get('/login')->assertSee('Session ended');
    }

    public function test_plain_guest_does_not_see_session_ended(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $this->get('/login')->assertDontSee('Session ended');
    }

    public function test_sign_in_sets_the_marker_and_sign_out_clears_it(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)->create(['password' => 'Correct-Horse-9-Battery']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-9-Battery'])
            ->assertRedirect('/dashboard')
            ->assertCookie(self::MARKER);

        $this->post('/logout')->assertCookieExpired(self::MARKER);
    }

    public function test_idle_sessions_are_logged_out_and_removed(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->create();
        $row = fn (string $id, ?int $userId, int $minutesAgo) => [
            'id' => $id, 'user_id' => $userId, 'ip_address' => '192.0.2.10', 'user_agent' => 'test',
            'payload' => '', 'last_activity' => now()->subMinutes($minutesAgo)->getTimestamp(),
        ];
        DB::table('sessions')->insert([
            $row('idle', $user->id, 16),   // past the 15-minute limit
            $row('fresh', $user->id, 5),   // still active
            $row('guest', null, 30),       // never signed in
        ]);

        $this->artisan('soc:expire-sessions')->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['id' => 'idle']);
        $this->assertDatabaseMissing('sessions', ['id' => 'guest']);
        $this->assertDatabaseHas('sessions', ['id' => 'fresh']);

        $log = ActivityLog::where('event', AuditEvent::AUTH_LOGOUT)->sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('idle_timeout', $log->properties['reason']);
    }

    public function test_laravel_does_not_delete_sessions_before_we_log_them(): void
    {
        $this->assertSame(0, config('session.lottery')[0]);
    }
}
