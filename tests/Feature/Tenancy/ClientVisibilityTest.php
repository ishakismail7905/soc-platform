<?php

namespace Tests\Feature\Tenancy;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class ClientVisibilityTest extends TestCase
{
    private Client $a;

    private Client $b;

    private Client $c;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->a, $this->b, $this->c] = Client::factory()->count(3)->create()->all();
    }

    public function test_all_client_roles_see_every_client(): void
    {
        foreach ([Role::SuperAdmin, Role::ItManager, Role::Auditor] as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->assertNull($user->accessibleClientIds(), $role->value);
            $this->assertSame(3, Client::visibleTo($user)->count(), $role->value);
        }
    }

    public function test_analyst_sees_only_assigned_clients(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->forClients($this->a, $this->c)->create();

        $this->assertEqualsCanonicalizing(
            [$this->a->id, $this->c->id],
            Client::visibleTo($user)->pluck('id')->all()
        );
        $this->assertTrue($user->canAccessClient($this->a));
        $this->assertFalse($user->canAccessClient($this->b));
    }

    public function test_client_viewer_sees_only_own_client(): void
    {
        $user = User::factory()->withRole(Role::ClientViewer)->forClients($this->b)->create();

        $this->assertSame([$this->b->id], Client::visibleTo($user)->pluck('id')->all());
        $this->assertFalse($user->canAccessClient($this->a->id));
    }

    public function test_analyst_with_no_assigned_clients_sees_nothing(): void
    {
        $user = User::factory()->withRole(Role::SocAnalyst)->create();

        $this->assertSame([], $user->accessibleClientIds());
        $this->assertSame(0, Client::visibleTo($user)->count());
    }

    public function test_user_without_role_sees_nothing_even_if_linked_to_a_client(): void
    {
        $user = User::factory()->forClients($this->a)->create();

        $this->assertSame([], $user->accessibleClientIds());
        $this->assertSame(0, Client::visibleTo($user)->count());
        $this->assertFalse($user->canAccessClient($this->a));
    }
}
