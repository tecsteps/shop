<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
