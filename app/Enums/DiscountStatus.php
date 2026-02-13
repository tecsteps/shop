<?php

namespace App\Enums;

enum DiscountStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Expired = 'expired';
    case Disabled = 'disabled';
}
