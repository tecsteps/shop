<?php

namespace App\Enums;

enum StoreUserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';
    case Support = 'support';

    /**
     * Roles allowed to delete a store or transfer ownership.
     *
     * @return list<self>
     */
    public static function ownerOnly(): array
    {
        return [self::Owner];
    }

    /**
     * Roles allowed to manage settings, themes, staff, and destructive actions.
     *
     * @return list<self>
     */
    public static function ownerOrAdmin(): array
    {
        return [self::Owner, self::Admin];
    }

    /**
     * Roles allowed to create/update operational resources (products, orders, etc.).
     *
     * @return list<self>
     */
    public static function ownerAdminOrStaff(): array
    {
        return [self::Owner, self::Admin, self::Staff];
    }

    /**
     * Every role; used for read-only listing and viewing.
     *
     * @return list<self>
     */
    public static function anyRole(): array
    {
        return self::cases();
    }
}
