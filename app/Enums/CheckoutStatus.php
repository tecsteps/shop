<?php

namespace App\Enums;

enum CheckoutStatus: string
{
    case Started = 'started';
    case Addressed = 'addressed';
    case ShippingSelected = 'shipping_selected';
    case PaymentSelected = 'payment_selected';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Expired], true);
    }
}
