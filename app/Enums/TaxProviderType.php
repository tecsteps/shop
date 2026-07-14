<?php

namespace App\Enums;

enum TaxProviderType: string
{
    case StripeTax = 'stripe_tax';
    case None = 'none';
}
