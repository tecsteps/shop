<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreDomain;

final class OrderStatusLink
{
    public function token(Order $order): string
    {
        return hash_hmac('sha256', $this->payload($order), (string) config('app.key'));
    }

    public function verify(Order $order, mixed $token): bool
    {
        return is_string($token) && hash_equals($this->token($order), $token);
    }

    public function url(Order $order): string
    {
        $hostname = StoreDomain::withoutGlobalScopes()
            ->where('store_id', $order->store_id)
            ->where('type', 'storefront')
            ->orderByDesc('is_primary')
            ->value('hostname');
        $configuredRoot = rtrim((string) config('app.url'), '/');
        $scheme = parse_url($configuredRoot, PHP_URL_SCHEME) ?: 'https';
        $root = is_string($hostname) && $hostname !== '' ? $scheme.'://'.$hostname : $configuredRoot;

        return $root.'/api/storefront/v1/orders/'.rawurlencode($order->order_number).'?'.http_build_query([
            'token' => $this->token($order),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function payload(Order $order): string
    {
        // Keep the documented v1 token stable while centralizing all producers and verification.
        return $order->order_number.'|'.$order->email;
    }
}
