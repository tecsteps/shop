<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $customers = Customer::withoutGlobalScopes()->where('store_id', $fashion->id)->get()->keyBy('email');
        $definitions = [
            ['#1001', 'customer@acme.test', 'credit_card', 'paid', 'paid', 'unfulfilled', 5497, 'classic-cotton-t-shirt', 2, 2],
            ['#1002', 'customer@acme.test', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 8997, 'premium-slim-fit-jeans', 1, 5],
            ['#1003', 'jane@example.com', 'credit_card', 'paid', 'paid', 'partial', 11997, 'organic-hoodie', 2, 4],
            ['#1004', 'customer@acme.test', 'credit_card', 'cancelled', 'refunded', 'unfulfilled', 2998, 'graphic-print-tee', 1, 12],
            ['#1005', 'jane@example.com', 'bank_transfer', 'pending', 'pending', 'unfulfilled', 3998, 'chino-shorts', 1, 1],
            ['#1006', 'michael@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 12498, 'running-sneakers', 1, 3],
            ['#1007', 'sarah@example.com', 'paypal', 'fulfilled', 'paid', 'fulfilled', 10496, 'cargo-pants', 2, 8],
            ['#1008', 'david@example.com', 'credit_card', 'paid', 'partially_refunded', 'fulfilled', 8997, 'premium-slim-fit-jeans', 1, 15],
            ['#1009', 'emma@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 4997, 'leather-belt', 1, 7],
            ['#1010', 'customer@acme.test', 'paypal', 'paid', 'paid', 'unfulfilled', 50498, 'cashmere-overcoat', 1, 6],
            ['#1011', 'james@example.com', 'credit_card', 'paid', 'paid', 'fulfilled', 3298, 'canvas-tote-bag', 1, 10],
            ['#1012', 'lisa@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 8497, 'striped-polo-shirt', 3, 9],
            ['#1013', 'robert@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 8497, 'wide-leg-trousers', 1, 11],
            ['#1014', 'anna@example.com', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 5000, 'gift-card', 1, 14],
            ['#1015', 'customer@acme.test', 'bank_transfer', 'paid', 'paid', 'unfulfilled', 5447, 'classic-cotton-t-shirt', 2, 2],
        ];
        foreach ($definitions as $definition) {
            [$number, $email, $method, $status, $financial, $fulfillment, $total, $handle, $quantity, $days] = $definition;
            $this->seedOrder($fashion, $customers[$email], $number, $method, $status, $financial, $fulfillment, $total, $handle, $quantity, $days);
        }

        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        $tech = Customer::withoutGlobalScopes()->where('store_id', $electronics->id)->where('email', 'techfan@example.com')->firstOrFail();
        $gadget = Customer::withoutGlobalScopes()->where('store_id', $electronics->id)->where('email', 'gadgetlover@example.com')->firstOrFail();
        $this->seedOrder($electronics, $tech, '#5001', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 121298, 'pro-laptop-15', 1, 6);
        $this->seedOrder($electronics, $gadget, '#5002', 'credit_card', 'paid', 'paid', 'unfulfilled', 14999, 'wireless-headphones', 1, 3);
        $this->seedOrder($electronics, $tech, '#5003', 'bank_transfer', 'pending', 'pending', 'unfulfilled', 4999, 'monitor-stand', 1, 1);
    }

    private function seedOrder(
        Store $store,
        Customer $customer,
        string $number,
        string $method,
        string $status,
        string $financial,
        string $fulfillmentStatus,
        int $total,
        string $productHandle,
        int $quantity,
        int $daysAgo,
    ): void {
        $address = $customer->addresses()->where('is_default', true)->value('address_json') ?? [];
        $shipping = $store->handle === 'acme-fashion' && $total !== 5000 ? 499 : 0;
        $subtotal = max(0, $total - $shipping);
        $order = Order::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'order_number' => $number], [
            'customer_id' => $customer->id, 'payment_method' => $method, 'status' => $status,
            'financial_status' => $financial, 'fulfillment_status' => $fulfillmentStatus, 'currency' => 'EUR',
            'subtotal_amount' => $subtotal, 'discount_amount' => 0, 'shipping_amount' => $shipping,
            'tax_amount' => 0, 'total_amount' => $total, 'email' => $customer->email,
            'billing_address_json' => $address, 'shipping_address_json' => $address, 'placed_at' => now()->subDays($daysAgo),
        ]);
        if ($order->lines()->exists()) {
            return;
        }

        $product = Product::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', $productHandle)->with('variants.inventoryItem')->firstOrFail();
        $variant = $product->variants->when($productHandle === 'gift-card', fn ($variants) => $variants->where('price_amount', 5000))->first() ?? $product->variants->firstOrFail();
        $line = $order->lines()->create([
            'product_id' => $product->id, 'variant_id' => $variant->id, 'title_snapshot' => $product->title,
            'sku_snapshot' => $variant->sku, 'quantity' => $quantity, 'unit_price_amount' => intdiv($subtotal, max(1, $quantity)),
            'total_amount' => $subtotal, 'tax_lines_json' => [['title' => 'VAT', 'rate' => 1900, 'amount' => 0]], 'discount_allocations_json' => [],
        ]);
        $payment = $order->payments()->create([
            'provider' => 'mock', 'method' => $method, 'provider_payment_id' => 'mock_seed_'.ltrim($number, '#'),
            'status' => $financial === 'pending' ? 'pending' : ($financial === 'refunded' ? 'refunded' : 'captured'),
            'amount' => $total, 'currency' => 'EUR', 'raw_json_encrypted' => ['seeded' => true],
        ]);

        if ($financial === 'pending' && $variant->inventoryItem !== null) {
            $variant->inventoryItem->increment('quantity_reserved', $quantity);
        }
        if ($fulfillmentStatus !== 'unfulfilled') {
            $shipment = $order->fulfillments()->create([
                'status' => $fulfillmentStatus === 'fulfilled' ? 'delivered' : 'shipped',
                'tracking_company' => 'DHL', 'tracking_number' => 'DHL'.ltrim($number, '#'),
                'tracking_url' => 'https://example.test/track/'.ltrim($number, '#'), 'shipped_at' => now()->subDays(max(0, $daysAgo - 1)),
            ]);
            $shipment->lines()->create(['order_line_id' => $line->id, 'quantity' => $fulfillmentStatus === 'partial' ? max(1, $quantity - 1) : $quantity]);
        }
        if ($financial === 'refunded' || $financial === 'partially_refunded') {
            $order->refunds()->create([
                'payment_id' => $payment->id, 'amount' => $financial === 'refunded' ? $total : min(2000, $total - 1),
                'reason' => $financial === 'refunded' ? 'Customer requested cancellation' : 'Partial item refund',
                'status' => 'processed', 'provider_refund_id' => 'mock_refund_seed_'.ltrim($number, '#'),
            ]);
        }
    }
}
