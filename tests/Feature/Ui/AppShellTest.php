<?php

namespace Tests\Feature\Ui;

use App\Enums\Role;
use App\Models\Client;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    /** Analysts and client viewers need a client; the others see all clients. */
    private function signInAs(Role $role): void
    {
        $needsClient = in_array($role, [Role::SocAnalyst, Role::ClientViewer], true);
        $this->actingAsRole($role, $needsClient ? [Client::factory()->create()] : []);
    }

    public function test_internal_roles_get_the_internal_dashboard(): void
    {
        $this->signInAs(Role::ItManager);

        $this->get('/dashboard')->assertOk()->assertViewIs('dashboard.internal');
    }

    public function test_client_viewer_gets_the_client_dashboard(): void
    {
        $this->signInAs(Role::ClientViewer);

        $this->get('/dashboard')->assertOk()->assertViewIs('dashboard.client');
    }

    public function test_auditor_gets_no_operational_dashboard(): void
    {
        // Task 10 changes this to a redirect to the activity log.
        $this->signInAs(Role::Auditor);

        $this->get('/dashboard')->assertRedirect(route('audit.index'));
    }

    /** Menus copied from the wireframes: [role, must show, must not show]. */
    public static function menus(): array
    {
        return [
            'super admin' => [Role::SuperAdmin,
                ['OVERVIEW', 'Dashboard', 'Alert Queue', 'Incident Cases', 'ADMINISTRATION', 'Correlation Rules',
                 'Alert Sources', 'Response Targets', 'Users & Roles', 'Clients', 'System Settings', 'Audit Log', 'OUTPUT', 'Reports'],
                ['My Workspace', 'My Cases', 'Monthly Reports', 'Closed Cases']],
            'it manager' => [Role::ItManager,
                ['OVERVIEW', 'Dashboard', 'Alert Queue', 'Incident Cases', 'CONFIGURATION', 'Correlation Rules', 'Reports'],
                ['Users & Roles', 'Clients', 'Alert Sources', 'System Settings', 'Audit Log', 'My Cases']],
            'soc analyst' => [Role::SocAnalyst,
                ['My Workspace', 'Alert Queue', 'My Cases'],
                ['Incident Cases', 'Correlation Rules', 'Reports', 'Users & Roles', 'Audit Log']],
            'client viewer' => [Role::ClientViewer,
                ['MY ORGANISATION', 'Dashboard & Incidents', 'Monthly Reports'],
                ['Alert Queue', 'Incident Cases', 'Users & Roles', 'Audit Log', 'Correlation Rules']],
            'auditor' => [Role::Auditor,
                ['READ-ONLY', 'Audit Trail', 'Activity Log', 'Closed Cases'],
                ['Alert Queue', 'Incident Cases', 'Users & Roles', 'Reports']],
        ];
    }

    #[DataProvider('menus')]
    public function test_each_role_sees_its_own_menu(Role $role, array $show, array $hide): void
    {
        $this->signInAs($role);
        $response = $this->followingRedirects()->get('/dashboard')->assertOk();

        foreach ($show as $text) {
            $response->assertSee($text);
        }
        foreach ($hide as $text) {
            $response->assertDontSee($text);
        }
    }

    public function test_pages_not_built_yet_are_disabled_not_links(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $html = $this->get('/dashboard')->getContent();

        $this->assertMatchesRegularExpression('/aria-disabled="true"[^>]*>\s*Alert Queue/', $html);
    }

    public function test_current_page_is_marked_active(): void
    {
        $this->signInAs(Role::SuperAdmin);
        $html = $this->get('/dashboard')->getContent();

        $this->assertMatchesRegularExpression('/class="nav-link active"[^>]*aria-current="page"[^>]*>\s*Dashboard/', $html);
    }

    public function test_top_bar_shows_role_scope_malaysia_time_and_logout(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 14:52', 'Asia/Kuala_Lumpur'));
        $client = Client::factory()->create(['name' => 'Client A']);
        $this->actingAsRole(Role::SocAnalyst, [$client]);

        $this->get('/dashboard')
            ->assertSee('SOC Analyst · Client A')
            ->assertSee('08 Nov 2026, 14:52 MYT')
            ->assertSee('action="'.route('logout').'"', false);
    }

    public function test_scope_labels_for_all_client_roles(): void
    {
        $this->signInAs(Role::ItManager);
        $this->get('/dashboard')->assertSee('IT Manager · All clients');

        $this->signInAs(Role::Auditor);
        $this->followingRedirects()->get('/dashboard')->assertSee('Auditor · Read-only');
    }
}