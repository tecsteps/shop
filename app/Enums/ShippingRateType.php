<?php

namespace App\Enums;

enum ShippingRateType: string
{
    case Flat = 'flat';
    case Weight = 'weight';
    case Price = 'price';
    case Carrier = 'carrier';
}
