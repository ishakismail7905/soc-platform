<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            return redirect()->route('audit.index'); // the Auditor's home is the activity log
        }

        abort(403); // no role, no dashboard (fail closed)
    }
}
