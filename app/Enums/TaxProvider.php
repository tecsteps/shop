<?php

namespace App\Enums;

enum TaxProvider: string
{
    case None = 'none';
    case StripeTax = 'stripe_tax';
}
