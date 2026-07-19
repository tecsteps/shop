<?php

namespace App\Support;

use App\Models\Order;

/**
 * HMAC-signed access token for the guest order status endpoint
 * (spec 02 §2.4). The token is included in confirmation emails and the
 * confirmation page; it proves the bearer knows the link without requiring
 * an account.
 */
class OrderToken
{
    /**
     * Generate the access token for the order.
     */
    public static function for(Order $order): string
    {
        return hash_hmac('sha256', $order->id.$order->order_number, (string) config('app.key'));
    }

    /**
     * Validate a token against the order (constant-time comparison).
     */
    public static function validate(Order $order, ?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::for($order), $token);
    }
}
