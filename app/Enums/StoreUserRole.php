<?php

namespace App\Enums;

enum StoreUserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';
    case Support = 'support';

    public function canManageStore(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canDeleteStore(): bool
    {
        return $this === self::Owner;
    }

    public function canManageProducts(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Staff], true);
    }

    public function canDeleteProducts(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canProcessRefunds(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canWriteOrders(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Staff], true);
    }

    public function canViewAnalytics(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Staff], true);
    }
}
