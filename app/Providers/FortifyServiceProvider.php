<?php

namespace App\Providers;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::authenticateUsing(fn (Request $request) => app(AuthenticateUser::class)($request));
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // NFR-1 rate limiting. Set above the 5-attempt lockout (Task 5),
        // so a user meets the lockout message first, not a throttle.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)
            ->by(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip()));

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)
            ->by($request->session()->get('login.id')));
    }
}
