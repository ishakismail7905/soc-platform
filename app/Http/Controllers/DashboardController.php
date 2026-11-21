<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->can('dashboard.internal')) {
            return view('dashboard.internal', [
                'title' => $user->role === Role::SocAnalyst ? 'My Workspace' : 'Dashboard',
            ]);
        }

        if ($user->can('portal.view')) {
            return view('dashboard.client');
        }

        if ($user->role === Role::Auditor) {
            // Task 10 builds the activity log; once it exists the Auditor lands there.
            return Route::has('audit.index') ? redirect()->route('audit.index') : view('dashboard.auditor');
        }

        abort(403); // no role, no dashboard (fail closed)
    }
}