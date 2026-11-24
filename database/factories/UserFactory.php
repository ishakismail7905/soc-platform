<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(Role $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function forClients(Client ...$clients): static
    {
        return $this->afterCreating(
            fn (User $user) => $user->clients()->attach(array_map(fn (Client $c) => $c->id, $clients))
        );
    }

    public function twoFactorConfirmed(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => encrypt(app(Google2FA::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(
                Collection::times(8, fn () => RecoveryCode::generate())->all()
            )),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
