<?php

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    /**
     * Expected permissions per role, written out by hand from report Table 3.2,
     * so the test does not simply copy the code it is checking.
     */
    public static function matrix(): array
    {
        $all = ['users.manage', 'clients.manage', 'rules.manage', 'sources.manage', 'targets.manage',
            'settings.manage', 'audit.view', 'audit.export', 'alerts.view', 'alerts.triage', 'cases.view',
            'cases.update_assigned', 'cases.assign', 'cases.change_severity', 'cases.close',
            'cases.print_own', 'cases.view_closed', 'reports.export', 'dashboard.internal'];

        return [
            'super_admin'   => ['super_admin', $all],
            'it_manager'    => ['it_manager', ['alerts.view', 'alerts.triage', 'cases.view', 'cases.update_assigned',
                                'cases.assign', 'cases.change_severity', 'cases.close', 'rules.manage',
                                'reports.export', 'dashboard.internal']],
            'soc_analyst'   => ['soc_analyst', ['alerts.view', 'alerts.triage', 'cases.view',
                                'cases.update_assigned', 'cases.print_own', 'dashboard.internal']],
            'client_viewer' => ['client_viewer', ['portal.view', 'portal.monthly_report', 'portal.comment']],
            'auditor'       => ['auditor', ['audit.view', 'audit.export', 'cases.view_closed']],
        ];
    }

    #[DataProvider('matrix')]
    public function test_role_has_exactly_its_permissions(string $role, array $expected): void
    {
        $actual = array_map(fn (Permission $p) => $p->value, Role::from($role)->permissions());
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    #[DataProvider('matrix')]
    public function test_gates_follow_the_matrix(string $role, array $expected): void
    {
        $user = User::factory()->make(['role' => Role::from($role)]);

        foreach (Permission::cases() as $p) {
            $this->assertSame(
                in_array($p->value, $expected, true),
                Gate::forUser($user)->allows($p->value),
                "{$role} / {$p->value}"
            );
        }
    }

    public function test_user_without_role_is_denied_everything(): void
    {
        $user = User::factory()->make(['role' => null]);

        foreach (Permission::cases() as $p) {
            $this->assertFalse(Gate::forUser($user)->allows($p->value), $p->value);
        }
    }

    public function test_there_are_22_permissions_and_client_viewer_has_only_portal_ones(): void
    {
        $this->assertCount(22, Permission::cases());

        foreach (Role::ClientViewer->permissions() as $p) {
            $this->assertStringStartsWith('portal.', $p->value);
        }
    }
}