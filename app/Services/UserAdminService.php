<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\UserAdminException;
use App\Models\User;
use App\Support\AuditEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Super Admin account management (FR-13). Every change runs in a transaction
 * and is written to the activity log; nothing is changed without a log row.
 */
class UserAdminService
{
    public function __construct(private ActivityLogger $logger) {}

    /** @return array{user: User, temporary_password: string} */
    public function create(array $data, User $actor): array
    {
        return DB::transaction(function () use ($data, $actor) {
            $temporary = Str::password(16);
            $clientIds = $this->cleanIds($data['client_ids'] ?? []);

            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $temporary]);
            $user->forceFill([
                'role' => Role::from($data['role']),
                'is_active' => true,
                'must_change_password' => true,
            ])->save();

            $user->clients()->sync($clientIds);
            $this->rememberPassword($user);

            $this->logger->log(AuditEvent::USER_CREATED, $user, [
                'role' => $user->role->value,
                'client_ids' => $clientIds,
            ], $actor);

            return ['user' => $user, 'temporary_password' => $temporary];
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $oldRole = $user->role;
            $newRole = Role::from($data['role']);

            if ($newRole !== $oldRole) {
                $this->refuseOwnAccount($user, $actor);
                $this->refuseLastSuperAdmin($user);
            }

            // Name and email: logged by field name only, never the values.
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);
            $changed = array_keys($user->getDirty());
            $user->forceFill(['role' => $newRole])->save();

            if ($changed !== []) {
                $this->logger->log(AuditEvent::USER_UPDATED, $user, ['changed' => $changed], $actor);
            }

            if ($newRole !== $oldRole) {
                $this->logger->log(AuditEvent::USER_ROLE_CHANGED, $user, [
                    'field' => 'role', 'from' => $oldRole?->value, 'to' => $newRole->value,
                ], $actor);
            }

            $before = $this->cleanIds($user->clients()->pluck('clients.id')->all());
            $after = $this->cleanIds($data['client_ids'] ?? []);

            if ($before !== $after) {
                $user->clients()->sync($after);
                $this->logger->log(AuditEvent::USER_ROLE_CHANGED, $user, [
                    'field' => 'clients', 'from' => $before, 'to' => $after,
                ], $actor);
            }

            return $user;
        });
    }

    public function deactivate(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $this->refuseOwnAccount($user, $actor);
            $this->refuseLastSuperAdmin($user);

            $user->forceFill(['is_active' => false])->save();
            $this->endSessions($user);
            $this->logger->log(AuditEvent::USER_DEACTIVATED, $user, [], $actor);
        });
    }

    public function reactivate(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $user->forceFill(['is_active' => true])->save();
            $this->logger->log(AuditEvent::USER_REACTIVATED, $user, [], $actor);
        });
    }

    public function unlock(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $user->forceFill(['locked_at' => null, 'failed_login_attempts' => 0])->save();
            $this->logger->log(AuditEvent::USER_UNLOCKED, $user, [], $actor);
        });
    }

    public function resetPassword(User $user, User $actor): string
    {
        return DB::transaction(function () use ($user, $actor) {
            $this->refuseOwnAccount($user, $actor);

            $temporary = Str::password(16);
            $user->forceFill(['password' => $temporary, 'must_change_password' => true])->save();
            $this->rememberPassword($user);
            $this->endSessions($user);
            $this->logger->log(AuditEvent::USER_PASSWORD_RESET, $user, [], $actor);

            return $temporary;
        });
    }

    public function resetTwoFactor(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $this->refuseOwnAccount($user, $actor);

            // The security gate (Task 6) forces re-enrolment on their next sign-in.
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
            $this->endSessions($user);
            $this->logger->log(AuditEvent::USER_2FA_RESET, $user, [], $actor);
        });
    }

    private function refuseOwnAccount(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw UserAdminException::ownAccount();
        }
    }

    /** Locked or deactivated Super Admins can't sign in, so they don't count. */
    private function refuseLastSuperAdmin(User $user): void
    {
        if ($user->role !== Role::SuperAdmin) {
            return;
        }

        $others = User::where('role', Role::SuperAdmin->value)
            ->where('is_active', true)
            ->whereNull('locked_at')
            ->whereKeyNot($user->getKey())
            ->lockForUpdate() // two admins acting at once can't both pass this check
            ->count();

        if ($others === 0) {
            throw UserAdminException::lastSuperAdmin();
        }
    }

    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /** A password the Super Admin has seen can never become the user's own. */
    private function rememberPassword(User $user): void
    {
        DB::table('password_histories')->insert([
            'user_id' => $user->id,
            'password' => $user->password, // already hashed by the model
            'created_at' => now(),
        ]);
    }

    /** @return list<int> sorted, unique */
    private function cleanIds(array $ids): array
    {
        return collect($ids)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
    }
}