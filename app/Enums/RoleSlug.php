<?php

namespace App\Enums;

/**
 * System role identifiers. Role *records* (names, permissions) live in the
 * database; these slugs are the stable keys code relies on.
 *
 * Owner, Operations and Finance are Govoyza staff roles. Client, Agency and
 * Partner are the single role of their account type.
 */
enum RoleSlug: string
{
    case Owner = 'owner';
    case Operations = 'operations';
    case Finance = 'finance';
    case Client = 'client';
    case Agency = 'agency';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Operations => 'Operations',
            self::Finance => 'Finance',
            self::Client => 'Client',
            self::Agency => 'Travel Agency',
            self::Partner => 'Partner',
        };
    }

    /** The only account type this role may be given to. */
    public function userType(): UserType
    {
        return match ($this) {
            self::Owner, self::Operations, self::Finance => UserType::Admin,
            self::Client => UserType::Customer,
            self::Agency => UserType::AgentUser,
            self::Partner => UserType::PartnerUser,
        };
    }

    /** @return list<self> */
    public static function staff(): array
    {
        return [self::Owner, self::Operations, self::Finance];
    }
}
