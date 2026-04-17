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
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function allowsFulfillment(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }
}
