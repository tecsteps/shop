<?php

namespace App\Enums;

enum DiscountValueType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';
    case FreeShipping = 'free_shipping';
}
