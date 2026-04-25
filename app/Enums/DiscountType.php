<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';
    case FreeShipping = 'free_shipping';
}

