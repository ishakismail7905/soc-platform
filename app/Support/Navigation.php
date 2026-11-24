<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The sidebar for each role, exactly as in the wireframes (03, 04, 14, 15, 17, 22).
 * A convenience only: every route still checks permissions on the server (NFR-2).
 */
final class Navigation
{
    /** @return list<array{heading: string, items: list<array{label: string, url: ?string, active: bool}>}> */
    public static function for(?User $user): array
    {
        $menu = match ($user?->role) {
            Role::SuperAdmin => [
                'OVERVIEW' => ['Dashboard' => 'dashboard'],
                'MONITORING' => ['Alert Queue' => 'alerts.index', 'Incident Cases' => 'cases.index'],
                'ADMINISTRATION' => [
                    'Correlation Rules' => 'rules.index',
                    'Alert Sources' => 'sources.index',
                    'Response Targets' => 'targets.index',
                    'Users & Roles' => 'admin.users.index',
                    'Clients' => 'admin.clients.index',
                    'System Settings' => 'settings.edit',
                    'Audit Log' => 'audit.index',
                ],
                'OUTPUT' => ['Reports' => 'reports.index'],
            ],
            Role::ItManager => [
                'OVERVIEW' => ['Dashboard' => 'dashboard'],
                'MONITORING' => ['Alert Queue' => 'alerts.index', 'Incident Cases' => 'cases.index'],
                'CONFIGURATION' => ['Correlation Rules' => 'rules.index'],
                'OUTPUT' => ['Reports' => 'reports.index'],
            ],
            Role::SocAnalyst => [
                'OVERVIEW' => ['My Workspace' => 'dashboard'],
                'MONITORING' => ['Alert Queue' => 'alerts.index', 'My Cases' => 'cases.index'],
            ],
            Role::ClientViewer => [
                'MY ORGANISATION' => ['Dashboard & Incidents' => 'dashboard', 'Monthly Reports' => 'portal.reports'],
            ],
            Role::Auditor => [
                'READ-ONLY' => ['Audit Trail' => 'audit.cases', 'Activity Log' => 'audit.index', 'Closed Cases' => 'cases.closed'],
            ],
            default => [], // no role: no menu (fail closed)
        };

        $sections = [];
        foreach ($menu as $heading => $items) {
            $links = [];
            foreach ($items as $label => $route) {
                $links[] = [
                    'label' => $label,
                    'url' => Route::has($route) ? route($route) : null, // not built yet: shown disabled
                    'active' => request()->routeIs($route),
                ];
            }
            $sections[] = ['heading' => $heading, 'items' => $links];
        }

        return $sections;
    }
}
