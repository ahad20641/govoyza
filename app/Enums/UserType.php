<?php

namespace App\Enums;

/**
 * What kind of account a user is, which decides the portal they use.
 * What they may do inside it comes from their role (see RoleSlug::userType()).
 */
enum UserType: string
{
    case Customer = 'customer';
    case AgentUser = 'agent_user';
    case PartnerUser = 'partner_user';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::AgentUser => 'Agency user',
            self::PartnerUser => 'Partner user',
            self::Admin => 'Govoyza staff',
        };
    }

    /** Route name of this account type's home dashboard. */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Customer => 'client.dashboard',
            self::AgentUser => 'agency.dashboard',
            self::PartnerUser => 'vendor.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }
}
