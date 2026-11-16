<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AuditEvent;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_self_registration_and_email_reset_do_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_valid_login_redirects_and_is_logged_with_role_and_ip(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)->create(['password' => 'Correct-Horse-9-Battery']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->post('/login', ['email' => $user->email, 'password' => 'Correct-Horse-9-Battery'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $log = ActivityLog::where('event', AuditEvent::AUTH_LOGIN)->sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('client_viewer', $log->role);
        $this->assertSame('10.0.0.5', $log->ip_address);
        $this->assertSame('10.0.0.5', $user->fresh()->last_login_ip);
    }

    public function test_email_is_case_insensitive(): void
    {
        User::factory()->withRole(Role::ClientViewer)
            ->create(['email' => 'aina@demo.test', 'password' => 'Correct-Horse-9-Battery']);

        $this->post('/login', ['email' => 'AINA@Demo.Test', 'password' => 'Correct-Horse-9-Battery'])
            ->assertRedirect('/dashboard');
    }

    public function test_wrong_password_and_unknown_email_get_the_same_message(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)->create();

        foreach ([$user->email, 'nobody@example.test'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong-password-123'])
                ->assertSessionHasErrors(['email' => 'Invalid email or password.']);
        }

        $this->assertGuest();
        $this->assertSame(2, ActivityLog::where('event', AuditEvent::AUTH_FAILED)->count());
    }

    public function test_logout_is_logged(): void
    {
        $this->actingAsRole(Role::SocAnalyst);

        $this->post('/logout');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::AUTH_LOGOUT]);
    }

    public function test_home_sends_guests_to_sign_in(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_home_sends_signed_in_users_to_their_dashboard(): void
    {
        $this->actingAsRole(Role::SocAnalyst);

        $this->get('/')->assertRedirect('/dashboard');
    }
}