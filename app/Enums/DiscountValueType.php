<?php

namespace App\Enums;

enum DiscountValueType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeShipping = 'free_shipping';
}
