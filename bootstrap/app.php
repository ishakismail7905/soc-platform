<?php

use App\Http\Middleware\EnsureAccountIsUsable;
use App\Http\Middleware\EnsureSecuritySetupComplete;
use App\Http\Middleware\SecurityHeaders;
use App\Listeners\TrackSignedInBrowser;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.usable' => EnsureAccountIsUsable::class,
            'security.setup' => EnsureSecuritySetupComplete::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Where guests go when a page needs sign-in. If this browser was signed in
        // and now has no session, the 15-minute idle timeout ended it (wireframe 20).
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->hasCookie(TrackSignedInBrowser::COOKIE)) {
                $request->session()->flash('timeout', true);
                Cookie::queue(Cookie::forget(TrackSignedInBrowser::COOKIE));
            }

            return route('login');
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // FR-10 / FR-14: every refused request is a security event, whether it came from
        // a can: check or from a scoped lookup (abort(403)). Logged, then the normal 403 page.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 403 && $request->user()) {
                rescue(fn () => app(ActivityLogger::class)->log(AuditEvent::ACCESS_DENIED, null, [
                    'url' => $request->path(),
                    'method' => $request->method(),
                ]));
            }

            return null; // let Laravel draw the 403 page from Task 7C
        });
    })->create();
