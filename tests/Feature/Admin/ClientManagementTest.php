<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\User;
use App\Support\AuditEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientManagementTest extends TestCase
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
    public function test_only_super_admin_manages_clients(Role $role): void
    {
        $this->actingAsRole($role, [Client::factory()->create()]);

        $this->get('/admin/clients')->assertForbidden();
        $this->post('/admin/clients', ['name' => 'Rogue Sdn Bhd', 'code' => 'RSB'])->assertForbidden();
        $this->assertDatabaseMissing('clients', ['code' => 'RSB']);
    }

    public function test_super_admin_adds_a_client_and_it_is_logged(): void
    {
        $this->actingAsRole(Role::SuperAdmin);

        $this->post('/admin/clients', ['name' => 'Demo Clinic Sdn Bhd', 'code' => ' dcs '])
            ->assertRedirect(route('admin.clients.index'));

        $client = Client::where('code', 'DCS')->sole(); // trimmed and upper-cased
        $this->assertTrue((bool) $client->is_active);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::CLIENT_CREATED, 'subject_id' => $client->id]);
    }

    public function test_codes_and_names_must_be_unique_and_well_formed(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        Client::factory()->create(['name' => 'Client A', 'code' => 'CLA']);

        $this->post('/admin/clients', ['name' => 'Another', 'code' => 'cla'])->assertSessionHasErrors('code');
        $this->post('/admin/clients', ['name' => 'Client A', 'code' => 'CLX'])->assertSessionHasErrors('name');
        $this->post('/admin/clients', ['name' => 'Bad Code', 'code' => 'C-1'])->assertSessionHasErrors('code');
        $this->post('/admin/clients', ['name' => 'Bad Code', 'code' => '<script>'])->assertSessionHasErrors('code');

        $this->assertSame(1, Client::count());
    }

    public function test_rename_is_logged_with_from_and_to(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $client = Client::factory()->create(['name' => 'Old Name', 'code' => 'OLD']);

        $this->put(route('admin.clients.update', $client), ['name' => 'New Name', 'code' => 'OLD'])
            ->assertSessionHasNoErrors();

        $this->assertSame('New Name', $client->fresh()->name);
        $log = ActivityLog::where('event', AuditEvent::CLIENT_UPDATED)->sole();
        $this->assertSame(['from' => 'Old Name', 'to' => 'New Name'], $log->properties['changed']['name']);
        $this->assertArrayNotHasKey('code', $log->properties['changed']); // unchanged fields aren't logged
    }

    public function test_clients_are_deactivated_never_deleted(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $client = Client::factory()->create();

        $this->post(route('admin.clients.deactivate', $client))->assertRedirect();
        $this->assertFalse((bool) $client->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['event' => AuditEvent::CLIENT_DEACTIVATED, 'subject_id' => $client->id]);

        $this->post(route('admin.clients.reactivate', $client))->assertRedirect();
        $this->assertTrue((bool) $client->fresh()->is_active);

        $this->delete('/admin/clients/'.$client->id)->assertStatus(405); // there is no delete
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_table_counts_assigned_analysts_and_viewers(): void
    {
        $this->actingAsRole(Role::SuperAdmin);
        $client = Client::factory()->create(['name' => 'Client A']);
        User::factory()->count(2)->withRole(Role::SocAnalyst)->forClients($client)->create();
        User::factory()->withRole(Role::ClientViewer)->forClients($client)->create();

        $this->get('/admin/clients')->assertOk()->assertSee('Client A')->assertSee('2 analysts · 1 viewer');
    }
}
