<?php

namespace App\Enums;

/**
 * The five roles from report Section 1.5.3 and Table 3.2.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case ItManager = 'it_manager';
    case SocAnalyst = 'soc_analyst';
    case ClientViewer = 'client_viewer';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ItManager => 'IT Manager',
            self::SocAnalyst => 'SOC Analyst',
            self::ClientViewer => 'Client Viewer',
            self::Auditor => 'Auditor',
        };
    }

    /** FR-12: TOTP is required for every role except Client Viewer. */
    public function requiresTwoFactor(): bool
    {
        return $this !== self::ClientViewer;
    }

    /** Roles that see every client's data; the others only see their assigned clients (FR-14). */
    public function seesAllClients(): bool
    {
        return in_array($this, [self::SuperAdmin, self::ItManager, self::Auditor], true);
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            // Everything internal, written out by rule rather than by hand so nothing is forgotten.
            // Portal permissions stay with Client Viewers only.
            self::SuperAdmin => array_values(array_filter(
                Permission::cases(),
                fn (Permission $p) => ! $p->isPortal()
            )),
            self::ItManager => [
                Permission::AlertsView, Permission::AlertsTriage,
                Permission::CasesView, Permission::CasesUpdateAssigned, Permission::CasesAssign,
                Permission::CasesChangeSeverity, Permission::CasesClose,
                Permission::RulesManage, Permission::ReportsExport, Permission::DashboardInternal,
            ],
            self::SocAnalyst => [
                Permission::AlertsView, Permission::AlertsTriage,
                Permission::CasesView, Permission::CasesUpdateAssigned, Permission::CasesPrintOwn,
                Permission::DashboardInternal,
            ],
            self::ClientViewer => [
                Permission::PortalView, Permission::PortalMonthlyReport, Permission::PortalComment,
            ],
            self::Auditor => [
                Permission::AuditView, Permission::AuditExport, Permission::CasesViewClosed,
            ],
        };
    }

    public function allows(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}