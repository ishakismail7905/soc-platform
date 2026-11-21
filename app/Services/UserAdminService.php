<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Super Admin account management (FR-13). Every change is written to the activity log.
 */
class UserAdminService
{
    public function __construct(private ActivityLogger $logger) {}

    /** @return array{user: User, temporary_password: string} */
    public function create(array $data, User $actor): array
    {
        return DB::transaction(function () use ($data, $actor) {
            $temporary = Str::password(16);
            $clientIds = array_map('intval', $data['client_ids'] ?? []);

            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $temporary]);
            // Security fields are set explicitly here, never from the request.
            $user->forceFill([
                'role' => Role::from($data['role']),
                'is_active' => true,
                'must_change_password' => true,
            ])->save();

            $user->clients()->sync($clientIds);

            // The Super Admin has seen this password, so it can never become the user's own.
            DB::table('password_histories')->insert([
                'user_id' => $user->id,
                'password' => $user->password, // already hashed by the model
                'created_at' => now(),
            ]);

            $this->logger->log(AuditEvent::USER_CREATED, $user, [
                'role' => $user->role->value,
                'client_ids' => $clientIds,
            ], $actor);

            return ['user' => $user, 'temporary_password' => $temporary];
        });
    }
}