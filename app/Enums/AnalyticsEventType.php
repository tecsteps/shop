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
     * Event types accepted by the public storefront ingestion endpoint.
     *
     * `product_view` is server-side only (tracked when a product page renders),
     * so it is excluded from the publicly accepted batch payload per the API spec.
     *
     * @return list<string>
     */
    public static function ingestibleValues(): array
    {
        return [
            self::PageView->value,
            self::AddToCart->value,
            self::RemoveFromCart->value,
            self::CheckoutStarted->value,
            self::CheckoutCompleted->value,
            self::Search->value,
        ];
    }
}
