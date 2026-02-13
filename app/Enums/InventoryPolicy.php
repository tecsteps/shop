<?php

namespace App\Enums;

enum InventoryPolicy: string
{
    case Deny = 'deny';
    case Continue = 'continue';
}
