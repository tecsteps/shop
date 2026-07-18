<?php

namespace App\Enums;

enum TaxProvider: string
{
    case StripeTax = 'stripe_tax';
    case None = 'none';
}
