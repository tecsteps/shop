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

    public function canWriteProducts(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Staff], true);
    }

    public function canProcessRefunds(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
