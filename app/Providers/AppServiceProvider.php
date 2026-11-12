<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One Laravel Gate per permission, so routes can use e.g. can:users.manage.
        // A user with no role is denied everything (fail closed).
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user) => $user->role?->allows($permission) ?? false
            );
        }
    }
}
