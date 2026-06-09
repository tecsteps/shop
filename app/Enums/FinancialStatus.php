<?php

namespace App\Enums;

enum FinancialStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Voided = 'voided';

    /**
     * Whether fulfillments may be created (spec 05 section 11.5 guard).
     */
    public function allowsFulfillment(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }
}
