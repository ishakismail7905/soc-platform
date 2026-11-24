<?php

namespace App\Enums;

/**
 * Every action the system can authorise. Granted to roles in Role::permissions(),
 * following report Table 3.2.
 */
enum Permission: string
{
    // Administration (Super Admin)
    case UsersManage = 'users.manage';
    case ClientsManage = 'clients.manage';
    case RulesManage = 'rules.manage';
    case SourcesManage = 'sources.manage';
    case TargetsManage = 'targets.manage';
    case SettingsManage = 'settings.manage';

    // Audit
    case AuditView = 'audit.view';
    case AuditExport = 'audit.export';

    // Alerts and cases
    case AlertsView = 'alerts.view';
    case AlertsTriage = 'alerts.triage';
    case CasesView = 'cases.view';
    case CasesUpdateAssigned = 'cases.update_assigned';
    case CasesAssign = 'cases.assign';
    case CasesChangeSeverity = 'cases.change_severity';
    case CasesClose = 'cases.close';
    case CasesPrintOwn = 'cases.print_own';
    case CasesViewClosed = 'cases.view_closed';

    // Output
    case ReportsExport = 'reports.export';
    case DashboardInternal = 'dashboard.internal';

    // Client portal (Client Viewer only)
    case PortalView = 'portal.view';
    case PortalMonthlyReport = 'portal.monthly_report';
    case PortalComment = 'portal.comment';

    public function isPortal(): bool
    {
        return str_starts_with($this->value, 'portal.');
    }
}
