<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Rebuilds the `testing` database and rolls back after each test,
    // so tests never see each other's data (and never touch `laravel`).
    use RefreshDatabase;

    /** Create a user with a role (and optional clients) and log in as them. */
    protected function actingAsRole(Role $role, array $clients = []): User
    {
        $user = User::factory()->withRole($role)->forClients(...$clients)->twoFactorConfirmed()->create();        $this->actingAs($user);

        return $user;
    }
}