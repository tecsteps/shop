<?php

namespace App\Enums;

enum AppInstallationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Uninstalled = 'uninstalled';
}
