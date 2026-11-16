<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    private const PASSWORD = 'Correct-Horse-9-Battery';

    /** The 6-digit code an authenticator app would show right now for this user. */
    private function otp(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
    }

    public static function internalRoles(): array
    {
        return [
            'super admin' => [Role::SuperAdmin],
            'it manager' => [Role::ItManager],
            'soc analyst' => [Role::SocAnalyst],
            'auditor' => [Role::Auditor],
        ];
    }

    #[DataProvider('internalRoles')]
    public function test_unenrolled_internal_user_is_sent_to_security_page(Role $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('security.edit'));
    }

    public function test_client_viewer_may_skip_two_factor(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)->forClients(Client::factory()->create())->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_user_without_role_is_sent_to_security_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect(route('security.edit'));
    }

    public function test_must_change_password_is_forced_even_when_enrolled(): void
    {
        $user = $this->actingAsRole(Role::SocAnalyst);
        $user->forceFill(['must_change_password' => true])->save();

        $this->get('/dashboard')->assertRedirect(route('security.edit'));
    }

    public function test_enrolled_user_reaches_the_dashboard(): void
    {
        $this->actingAsRole(Role::SocAnalyst);

        $this->get('/dashboard')->assertOk();
    }

    public function test_security_page_opens_for_an_unenrolled_user(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->create();

        $this->actingAs($user)->get('/security')->assertOk()->assertSee('Set up two-factor authentication');
    }

    public function test_every_app_route_behind_auth_has_the_security_gate(): void
    {
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();

            if (in_array('auth', $middleware, true) && $route->getName() !== 'security.edit') {
                $this->assertContains('security.setup', $middleware, "Route /{$route->uri()} skips security.setup");
                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked, 'No app routes checked: the test would pass by accident');
    }

    public function test_enrol_then_sign_in_with_a_code(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->create(['password' => self::PASSWORD]);

        // Enrol. Fortify wants the password confirmed recently before any 2FA change.
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication')
            ->assertRedirect();
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $this->otp($user)])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::AUTH_2FA_ENABLED, 'subject_id' => $user->id]);

        // Each code is accepted once (replay protection). Forget used codes so this
        // test can sign in again inside the same 30-second window.
        Cache::flush();

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        // Wrong code: still a guest, and the SOC sees it.
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::AUTH_2FA_FAILED, 'subject_id' => $user->id]);

        // Right code: in.
        $this->post('/two-factor-challenge', ['code' => $this->otp($user)])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_disabling_two_factor_is_logged_and_forces_re_enrolment(): void
    {
        $user = $this->actingAsRole(Role::SocAnalyst);

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->delete('/user/two-factor-authentication')
            ->assertRedirect();

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::AUTH_2FA_DISABLED, 'subject_id' => $user->id]);
        $this->get('/dashboard')->assertRedirect(route('security.edit'));
    }

    public function test_recovery_code_works_once_and_is_logged(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->twoFactorConfirmed()->create(['password' => self::PASSWORD]);
        $code = $user->recoveryCodes()[0];

        $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect('/two-factor-challenge');
        $this->post('/two-factor-challenge', ['recovery_code' => $code])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::AUTH_RECOVERY_CODE_USED, 'subject_id' => $user->id]);
        $this->assertNotContains($code, $user->fresh()->recoveryCodes());
    }
}