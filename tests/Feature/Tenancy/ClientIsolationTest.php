<?php

namespace Tests\Feature\Tenancy;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Support\AuditEvent;
use Tests\TestCase;

class ClientIsolationTest extends TestCase
{
    public function test_client_viewer_gets_the_same_403_for_another_client_and_a_missing_one(): void
    {
        [$mine, $other] = Client::factory()->count(2)->create()->all();
        $this->actingAsRole(Role::ClientViewer, [$mine]);

        $foreign = $this->get("/clients/{$other->id}")->assertForbidden()->assertDontSee($other->name);
        $missing = $this->get('/clients/999999')->assertForbidden();
        $junk = $this->get('/clients/abc')->assertForbidden();

        // Byte-for-byte identical: the response can't reveal whether a record exists.
        $this->assertSame($missing->getContent(), $foreign->getContent());
        $this->assertSame($missing->getContent(), $junk->getContent());

        $this->get("/clients/{$mine->id}")->assertOk()->assertSee($mine->name);
    }

    public function test_analyst_is_limited_to_assigned_clients_and_managers_see_all(): void
    {
        [$a, $b] = Client::factory()->count(2)->create()->all();

        $this->actingAsRole(Role::SocAnalyst, [$a]);
        $this->get("/clients/{$a->id}")->assertOk();
        $this->get("/clients/{$b->id}")->assertForbidden();

        $this->actingAsRole(Role::ItManager);
        $this->get("/clients/{$b->id}")->assertOk();
    }

    public function test_every_refused_request_is_logged_as_access_denied(): void
    {
        [$mine, $other] = Client::factory()->count(2)->create()->all();
        $viewer = $this->actingAsRole(Role::ClientViewer, [$mine]);

        $this->get("/clients/{$other->id}")->assertForbidden(); // IDOR attempt
        $this->get('/admin/clients')->assertForbidden();         // admin page

        $logs = ActivityLog::where('event', AuditEvent::ACCESS_DENIED)->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame("clients/{$other->id}", $logs[0]->properties['url']);
        $this->assertSame('admin/clients', $logs[1]->properties['url']);
        $this->assertSame('GET', $logs[0]->properties['method']);
        $this->assertTrue($logs->every(fn (ActivityLog $log) => $log->user_id === $viewer->id));
    }
}
