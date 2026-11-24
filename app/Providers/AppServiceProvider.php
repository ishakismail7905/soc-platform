<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        // Every time the sidebar is drawn, give it the menu for the signed-in user's role.
        View::composer('layouts.partials.sidebar', fn ($view) => $view->with('navigation', Navigation::for(auth()->user())));

        Paginator::useBootstrapFive();
    }
}
