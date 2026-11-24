<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\DB;

/** Super Admin client management (wireframe 22). Every change is logged; clients are never deleted. */
class ClientAdminService
{
    public function __construct(private ActivityLogger $logger) {}

    public function create(array $data, User $actor): Client
    {
        return DB::transaction(function () use ($data, $actor) {
            $client = Client::create(['name' => $data['name'], 'code' => $data['code'], 'is_active' => true]);

            $this->logger->log(AuditEvent::CLIENT_CREATED, $client, [
                'client_id' => $client->id, 'name' => $client->name, 'code' => $client->code,
            ], $actor);

            return $client;
        });
    }

    public function update(Client $client, array $data, User $actor): Client
    {
        return DB::transaction(function () use ($client, $data, $actor) {
            $client->fill(['name' => $data['name'], 'code' => $data['code']]);

            $changed = [];
            foreach ($client->getDirty() as $field => $new) {
                $changed[$field] = ['from' => $client->getOriginal($field), 'to' => $new];
            }

            if ($changed !== []) {
                $client->save();
                $this->logger->log(AuditEvent::CLIENT_UPDATED, $client, [
                    'client_id' => $client->id, 'changed' => $changed,
                ], $actor);
            }

            return $client;
        });
    }

    public function setActive(Client $client, bool $active, User $actor): void
    {
        DB::transaction(function () use ($client, $active, $actor) {
            $client->forceFill(['is_active' => $active])->save();

            $this->logger->log(
                $active ? AuditEvent::CLIENT_REACTIVATED : AuditEvent::CLIENT_DEACTIVATED,
                $client,
                ['client_id' => $client->id],
                $actor
            );
        });
    }
}
