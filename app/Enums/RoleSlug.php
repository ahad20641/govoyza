<?php

namespace App\Enums;

/**
 * System role identifiers. Role *records* (names, permissions) live in the
 * database; these slugs are the stable keys code relies on.
 */
enum RoleSlug: string
{
    case SuperAdmin = 'super_admin';
    case Client = 'client';
    case Agency = 'agency';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Client => 'Client',
            self::Agency => 'Travel Agency',
            self::Vendor => 'Vendor',
        };
    }

    /** Route name of this role's home dashboard. */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'admin.dashboard',
            self::Client => 'client.dashboard',
            self::Agency => 'agency.dashboard',
            self::Vendor => 'vendor.dashboard',
        };
    }
}
