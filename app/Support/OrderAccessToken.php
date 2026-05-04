<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class OrderAccessToken
{
    public static function make(Order $order): string
    {
        return hash_hmac('sha256', self::payload($order), self::key());
    }

    public static function valid(Order $order, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals(self::make($order), $token);
    }

    private static function payload(Order $order): string
    {
        return implode('|', [
            $order->store_id,
            $order->getKey(),
            $order->order_number,
            $order->created_at?->timestamp ?? 0,
        ]);
    }

    private static function key(): string
    {
        $key = (string) Config::get('app.key');

        return Str::startsWith($key, 'base64:')
            ? base64_decode(Str::after($key, 'base64:'), true) ?: $key
            : $key;
    }
}
