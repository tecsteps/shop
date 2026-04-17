<?php

namespace App\Enums;

enum StoreUserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';
    case Support = 'support';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function isOwnerOrAdmin(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function isOwnerAdminOrStaff(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Staff], true);
    }
}
