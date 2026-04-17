<?php

namespace App\Enums;

enum AnalyticsEventType: string
{
    case PageView = 'page_view';
    case ProductView = 'product_view';
    case AddToCart = 'add_to_cart';
    case RemoveFromCart = 'remove_from_cart';
    case CheckoutStarted = 'checkout_started';
    case CheckoutCompleted = 'checkout_completed';
    case Search = 'search';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
