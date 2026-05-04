<?php

namespace App\Support;

use App\Models\Checkout;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class CheckoutAccessToken
{
    public static function make(Checkout $checkout): string
    {
        return hash_hmac('sha256', self::payload($checkout), self::key());
    }

    public static function valid(Checkout $checkout, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals(self::make($checkout), $token);
    }

    private static function payload(Checkout $checkout): string
    {
        return implode('|', [
            $checkout->store_id,
            $checkout->getKey(),
            $checkout->cart_id,
            $checkout->created_at?->timestamp ?? 0,
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
