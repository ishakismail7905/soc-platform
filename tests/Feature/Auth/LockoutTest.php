<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Events\AccountLockedOut;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LockoutTest extends TestCase
{
    private const LOCKED = 'This account is locked. Contact your Super Admin.';

    private const INVALID = 'Invalid email or password.';

    protected function setUp(): void
    {
        parent::setUp();

        // A stand-in protected page until the real dashboard exists (Task 7).
        Route::middleware(['web', 'auth', 'account.usable'])->get('/_test/protected', fn () => 'ok');
    }

    private function attempt(string $email, string $password = 'definitely-wrong-1A!'): TestResponse
    {
        return $this->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function test_fifth_failure_locks_the_account_and_raises_an_alert_event(): void
    {
        Event::fake([AccountLockedOut::class]);
        $user = User::factory()->withRole(Role::SocAnalyst)->create();

        foreach (range(1, 4) as $_) {
            $this->attempt($user->email)->assertSessionHasErrors(['email' => self::INVALID]);
        }
        $this->assertNull($user->fresh()->locked_at);

        $this->attempt($user->email)->assertSessionHasErrors(['email' => self::LOCKED]);

        $this->assertNotNull($user->fresh()->locked_at);
        Event::assertDispatched(AccountLockedOut::class, fn ($e) => $e->user->is($user));
        $this->assertDatabaseHas('activity_logs', [
            'event' => AuditEvent::AUTH_LOCKOUT, 'subject_id' => $user->id,
        ]);
    }

    public function test_each_failure_is_counted_and_logged_exactly_once(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->create();

        $this->attempt($user->email);

        $this->assertSame(1, $user->fresh()->failed_login_attempts);
        $this->assertSame(1, ActivityLog::where('event', AuditEvent::AUTH_FAILED)->count());
    }

    public function test_locked_user_is_refused_even_with_the_correct_password(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)
            ->create(['password' => 'Correct-Horse-9-Battery', 'locked_at' => now()]);

        $this->attempt($user->email, 'Correct-Horse-9-Battery')
            ->assertSessionHasErrors(['email' => self::LOCKED]);

        $this->assertGuest();
    }

    public function test_successful_login_resets_the_counter(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)
            ->create(['password' => 'Correct-Horse-9-Battery', 'failed_login_attempts' => 3]);

        $this->attempt($user->email, 'Correct-Horse-9-Battery')->assertRedirect('/dashboard');

        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_unknown_email_behaves_exactly_like_a_real_account(): void
    {
        foreach (range(1, 4) as $_) {
            $this->attempt('ghost@example.test')->assertSessionHasErrors(['email' => self::INVALID]);
        }
        $this->attempt('ghost@example.test')->assertSessionHasErrors(['email' => self::LOCKED]);
        $this->attempt('ghost@example.test')->assertSessionHasErrors(['email' => self::LOCKED]);

        $this->assertSame(0, User::whereNotNull('locked_at')->count());
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)
            ->create(['password' => 'Correct-Horse-9-Battery', 'is_active' => false]);

        $this->attempt($user->email, 'Correct-Horse-9-Battery')
            ->assertSessionHasErrors(['email' => self::INVALID]);

        $this->assertGuest();
    }

    public function test_deactivated_user_is_thrown_out_on_the_next_request(): void
    {
        $user = $this->actingAsRole(Role::SocAnalyst);
        $user->forceFill(['is_active' => false])->save();

        $this->get('/_test/protected')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::AUTH_SESSION_TERMINATED]);
    }

    public function test_locked_user_is_thrown_out_on_the_next_request(): void
    {
        $user = $this->actingAsRole(Role::SocAnalyst);
        $user->forceFill(['locked_at' => now()])->save();

        $this->get('/_test/protected')->assertRedirect('/login');

        $this->assertGuest();
    }
}
