<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.usable' => \App\Http\Middleware\EnsureAccountIsUsable::class,
            'security.setup' => \App\Http\Middleware\EnsureSecuritySetupComplete::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Where guests go when a page needs sign-in. If this browser was signed in
        // and now has no session, the 15-minute idle timeout ended it (wireframe 20).
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->hasCookie(\App\Listeners\TrackSignedInBrowser::COOKIE)) {
                $request->session()->flash('timeout', true);
                Cookie::queue(Cookie::forget(\App\Listeners\TrackSignedInBrowser::COOKIE));
            }

            return route('login');
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
