<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Rules\PasswordPolicy;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public static function nonAdmins(): array
    {
        return [
            'it manager' => [Role::ItManager],
            'soc analyst' => [Role::SocAnalyst],
            'client viewer' => [Role::ClientViewer],
            'auditor' => [Role::Auditor],
        ];
    }

    #[DataProvider('nonAdmins')]
    public function test_only_super_admin_can_open_or_use_user_admin(Role $role): void
    {
        $this->actingAsRole($role, [Client::factory()->create()]);

        $this->get('/admin/users')->assertForbidden();

        // Privilege escalation attempt: post a new Super Admin straight to the endpoint.
        $this->post('/admin/users', ['name' => 'X', 'email' => 'x@demo.test', 'role' => 'super_admin'])
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@demo.test']);
    }

    public function test_super_admin_sees_the_user_table_and_it_is_never_cached(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        User::factory()->withRole(Role::SocAnalyst)->create(['name' => 'Aiman R.', 'locked_at' => now()]);

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('Aiman R.')
            ->assertSee('SOC Analyst')
            ->assertSee('LOCKED')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_create_analyst_with_clients_and_a_one_time_temporary_password(): void
    {
        $admin = $this->actingAsRole(Role::SuperAdmin);
        [$a, $b] = Client::factory()->count(2)->create()->all();

        $this->post('/admin/users', [
            'name' => 'Aina', 'email' => 'Aina@Demo.test', 'role' => 'soc_analyst', 'client_ids' => [$a->id, $b->id],
        ])->assertRedirect(route('admin.users.index'))->assertSessionHas('temporary_password');

        $temporary = session('temporary_password');
        $user = User::whereEmail('aina@demo.test')->sole(); // stored in lower case, like login

        $this->assertSame(Role::SocAnalyst, $user->role);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $user->clients->pluck('id')->all());
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temporary, $user->password));
        $this->assertGreaterThanOrEqual(16, strlen($temporary));

        $log = ActivityLog::where('event', AuditEvent::USER_CREATED)->sole();
        $this->assertSame($user->id, $log->subject_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringNotContainsString($temporary, json_encode($log->properties));
    }

    public function test_temporary_password_cannot_be_kept_as_the_new_password(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $this->post('/admin/users', ['name' => 'Bob', 'email' => 'bob@demo.test', 'role' => 'auditor']);
        $temporary = session('temporary_password');
        $user = User::whereEmail('bob@demo.test')->sole();

        $validator = Validator::make(
            ['password' => $temporary, 'password_confirmation' => $temporary],
            ['password' => PasswordPolicy::rules($user)]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_client_assignment_follows_the_role(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        [$a, $b] = Client::factory()->count(2)->create()->all();
        $inactive = Client::factory()->create(['is_active' => false]);
        $post = fn (array $data) => $this->post('/admin/users', ['name' => 'T', 'email' => uniqid().'@demo.test'] + $data);

        $post(['role' => 'client_viewer', 'client_ids' => [$a->id, $b->id]])->assertSessionHasErrors('client_ids');
        $post(['role' => 'client_viewer'])->assertSessionHasErrors('client_ids');
        $post(['role' => 'soc_analyst'])->assertSessionHasErrors('client_ids');
        $post(['role' => 'it_manager', 'client_ids' => [$a->id]])->assertSessionHasErrors('client_ids');
        $post(['role' => 'soc_analyst', 'client_ids' => [$inactive->id]])->assertSessionHasErrors('client_ids.0');

        $post(['role' => 'client_viewer', 'client_ids' => [$a->id]])->assertSessionHasNoErrors();
        $this->assertSame(1, User::where('role', 'client_viewer')->count());
    }

    public function test_unknown_role_and_duplicate_email_are_rejected(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        User::factory()->create(['email' => 'taken@demo.test']);

        $this->post('/admin/users', ['name' => 'T', 'email' => 'TAKEN@demo.test', 'role' => 'auditor'])
            ->assertSessionHasErrors('email');
        $this->post('/admin/users', ['name' => 'T', 'email' => 'new@demo.test', 'role' => 'god_mode'])
            ->assertSessionHasErrors('role');
    }

    public function test_extra_fields_in_the_request_are_ignored(): void
    {
        $this->actingAsRole(Role::SuperAdmin);

        $this->post('/admin/users', [
            'name' => 'Eve', 'email' => 'eve@demo.test', 'role' => 'auditor',
            'must_change_password' => 0, 'password' => 'chosen-by-attacker-123', 'is_active' => 0,
        ]);

        $user = User::whereEmail('eve@demo.test')->sole();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->is_active);
        $this->assertFalse(Hash::check('chosen-by-attacker-123', $user->password));
    }

    public function test_permissions_tab_is_drawn_from_the_code(): void
    {
        $this->actingAsRole(Role::SuperAdmin);

        $response = $this->get('/admin/users/permissions')->assertOk()->assertSee('Auditor');

        foreach (Permission::cases() as $permission) {
            $response->assertSee($permission->value);
        }
    }
}