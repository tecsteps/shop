<?php

namespace App\Enums;

enum AppInstallationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Uninstalled = 'uninstalled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Uninstalled => 'Uninstalled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'amber',
            self::Uninstalled => 'zinc',
        };
    }
}
