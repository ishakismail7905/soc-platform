<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AuditEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every protected request. If the Super Admin deactivated or the system locked
 * this account after it logged in, end the session immediately (FR-13 "deactivate instantly").
 */
class EnsureAccountIsUsable
{
    public function __construct(private ActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && (! $user->is_active || $user->locked_at !== null)) {
            $this->logger->log(AuditEvent::AUTH_SESSION_TERMINATED, $user, [
                'reason' => $user->is_active ? 'locked' : 'inactive',
            ], $user);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Your session has ended. Contact your Super Admin if this continues.');
        }

        return $next($request);
    }
}