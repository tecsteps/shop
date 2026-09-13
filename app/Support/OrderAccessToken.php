<?php

namespace App\Support;

use App\Models\Order;

/**
 * HMAC-signed order access tokens for guest order lookup per spec 02 2.4.
 */
class OrderAccessToken
{
    public static function generate(Order $order): string
    {
        return hash_hmac('sha256', $order->store_id.':'.$order->order_number, (string) config('app.key'));
    }

    public static function verify(Order $order, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals(self::generate($order), $token);
    }
}
