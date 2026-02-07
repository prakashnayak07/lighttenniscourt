<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Staff = 'staff';
    case Coach = 'coach';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Staff => 'Staff',
            self::Coach => 'Coach',
            self::Customer => 'Customer',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::Staff]);
    }

    public function canAccessFilament(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::Staff]);
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin]);
    }
}
