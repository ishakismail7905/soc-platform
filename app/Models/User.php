<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Defaults for a brand-new user, matching the database column defaults.
     * Without these, a freshly created user has is_active = null until it is
     * reloaded, and the fail-closed account.usable check treats null as inactive.
     */
    protected $attributes = [
        'is_active' => true,
        'failed_login_attempts' => 0,
        'must_change_password' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'locked_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function primaryRole(): ?Role
    {
        return $this->role;
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    public function passwordHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    /**
     * Which client IDs this user may see (FR-14).
     * null = every client; [] = none. Fails closed for users without a role.
     *
     * @return list<int>|null
     */
    public function accessibleClientIds(): ?array
    {
        if ($this->role === null) {
            return [];
        }

        if ($this->role->seesAllClients()) {
            return null;
        }

        return $this->clients()->pluck('clients.id')->all();
    }

    public function canAccessClient(Client|int $client): bool
    {
        $id = $client instanceof Client ? $client->id : $client;
        $ids = $this->accessibleClientIds();

        return $ids === null || in_array($id, $ids, true);
    }
}