<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

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
        // Production and staging serve only HTTPS (NFR-1): every generated link uses https://.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

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
