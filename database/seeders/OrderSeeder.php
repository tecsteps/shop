<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Seed the demo orders (spec 07 section 3.13): 15 Acme Fashion orders
     * across all statuses and 3 minimal Acme Electronics orders for tenant
     * isolation testing.
     */
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ($this->fashionOrders($fashion) as $definition) {
            $this->seedOrder($fashion, $definition);
        }

        foreach ($this->electronicsOrders() as $definition) {
            $this->seedOrder($electronics, $definition);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedOrder(Store $store, array $definition): void
    {
        $exists = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('order_number', $definition['number'])
            ->exists();

        if ($exists) {
            return;
        }

        $customer = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('email', $definition['customer'])
            ->firstOrFail();

        $shippingAddress = $customer->addresses()->where('is_default', true)->first()?->address_json;

        $totals = $definition['totals'];

        $order = new Order([
            'customer_id' => $customer->getKey(),
            'order_number' => $definition['number'],
            'payment_method' => $definition['payment_method'],
            'status' => $definition['status'],
            'financial_status' => $definition['financial_status'],
            'fulfillment_status' => $definition['fulfillment_status'],
            'currency' => 'EUR',
            'subtotal_amount' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'shipping_amount' => $totals['shipping'],
            'tax_amount' => $totals['tax'],
            'total_amount' => $totals['total'],
            'email' => $customer->email,
            'billing_address_json' => $shippingAddress,
            'shipping_address_json' => $shippingAddress,
            'placed_at' => $definition['placed_at'],
        ]);
        $order->store_id = $store->getKey();
        $order->save();

        $orderLines = [];

        foreach ($definition['lines'] as $line) {
            $variant = $this->findVariant($store, $line['product'], $line['values'] ?? []);
            $optionLabels = implode(' / ', $line['values'] ?? []);

            $orderLines[] = $order->lines()->create([
                'product_id' => $variant?->product_id,
                'variant_id' => $variant?->getKey(),
                'title_snapshot' => $line['product'].($optionLabels !== '' ? " ({$optionLabels})" : ''),
                'sku_snapshot' => $variant?->sku,
                'quantity' => $line['quantity'],
                'unit_price_amount' => $line['unit_price'],
                'total_amount' => $line['unit_price'] * $line['quantity'] - ($line['discount'] ?? 0),
                'tax_lines_json' => [],
                'discount_allocations_json' => isset($line['discount'])
                    ? [[
                        'discount_id' => Discount::query()
                            ->withoutGlobalScopes()
                            ->where('store_id', $store->getKey())
                            ->where('code', 'WELCOME10')
                            ->first()?->getKey(),
                        'amount' => $line['discount'],
                    ]]
                    : [],
            ]);
        }

        $payment = $order->payments()->create([
            'provider' => 'mock',
            'method' => $definition['payment_method'],
            'provider_payment_id' => $definition['payment']['reference'],
            'status' => $definition['payment']['status'],
            'amount' => $totals['total'],
            'currency' => 'EUR',
        ]);

        if (isset($definition['fulfillment'])) {
            $fulfillmentData = $definition['fulfillment'];

            $fulfillment = $order->fulfillments()->create([
                'status' => $fulfillmentData['status'],
                'tracking_company' => $fulfillmentData['tracking_company'] ?? null,
                'tracking_number' => $fulfillmentData['tracking_number'] ?? null,
                'shipped_at' => $fulfillmentData['shipped_at'],
                'delivered_at' => $fulfillmentData['status'] === 'delivered'
                    ? ($fulfillmentData['delivered_at'] ?? now())
                    : null,
            ]);

            $fulfilledIndexes = $fulfillmentData['line_indexes'] ?? array_keys($orderLines);

            foreach ($fulfilledIndexes as $index) {
                $fulfillment->lines()->create([
                    'order_line_id' => $orderLines[$index]->getKey(),
                    'quantity' => $orderLines[$index]->quantity,
                ]);
            }
        }

        if (isset($definition['refund'])) {
            $order->refunds()->create([
                'payment_id' => $payment->getKey(),
                'amount' => $definition['refund']['amount'],
                'reason' => $definition['refund']['reason'],
                'status' => 'processed',
                'provider_refund_id' => $definition['refund']['reference'],
            ]);
        }
    }

    /**
     * Find a variant by product title and option value labels.
     *
     * @param  list<string>  $values
     */
    private function findVariant(Store $store, string $productTitle, array $values): ?ProductVariant
    {
        $query = ProductVariant::query()
            ->whereHas('product', fn ($builder) => $builder
                ->withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('title', $productTitle));

        foreach ($values as $value) {
            $query->whereHas('optionValues', fn ($builder) => $builder->where('value', $value));
        }

        return $query->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fashionOrders(Store $store): array
    {
        return [
            [
                'number' => '#1001',
                'customer' => 'customer@acme.test',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDays(2),
                'lines' => [
                    ['product' => 'Classic Cotton T-Shirt', 'values' => ['S', 'White'], 'quantity' => 2, 'unit_price' => 2499],
                ],
                'totals' => ['subtotal' => 4998, 'discount' => 0, 'shipping' => 499, 'tax' => 798, 'total' => 5497],
                'payment' => ['reference' => 'mock_test_order1001', 'status' => 'captured'],
            ],
            [
                'number' => '#1002',
                'customer' => 'customer@acme.test',
                'payment_method' => 'credit_card',
                'status' => 'fulfilled', 'financial_status' => 'paid', 'fulfillment_status' => 'fulfilled',
                'placed_at' => now()->subDays(10),
                'lines' => [
                    ['product' => 'Organic Hoodie', 'values' => ['M'], 'quantity' => 1, 'unit_price' => 5999],
                    ['product' => 'Classic Cotton T-Shirt', 'values' => ['L', 'Black'], 'quantity' => 1, 'unit_price' => 2499],
                ],
                'totals' => ['subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997],
                'payment' => ['reference' => 'mock_test_order1002', 'status' => 'captured'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL1234567890', 'shipped_at' => now()->subDays(8)],
            ],
            [
                'number' => '#1003',
                'customer' => 'jane@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'partial',
                'placed_at' => now()->subDays(5),
                'lines' => [
                    ['product' => 'Premium Slim Fit Jeans', 'values' => ['32', 'Blue'], 'quantity' => 1, 'unit_price' => 7999],
                    ['product' => 'Leather Belt', 'values' => ['L/XL', 'Brown'], 'quantity' => 1, 'unit_price' => 3499],
                ],
                'totals' => ['subtotal' => 11498, 'discount' => 0, 'shipping' => 499, 'tax' => 1836, 'total' => 11997],
                'payment' => ['reference' => 'mock_test_order1003', 'status' => 'captured'],
                'fulfillment' => ['status' => 'shipped', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL9876543210', 'shipped_at' => now()->subDays(3), 'line_indexes' => [0]],
            ],
            [
                'number' => '#1004',
                'customer' => 'customer@acme.test',
                'payment_method' => 'credit_card',
                'status' => 'cancelled', 'financial_status' => 'refunded', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDays(15),
                'lines' => [
                    ['product' => 'Classic Cotton T-Shirt', 'values' => ['M', 'Navy'], 'quantity' => 1, 'unit_price' => 2499],
                ],
                'totals' => ['subtotal' => 2499, 'discount' => 0, 'shipping' => 499, 'tax' => 399, 'total' => 2998],
                'payment' => ['reference' => 'mock_test_order1004', 'status' => 'refunded'],
                'refund' => ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'reference' => 'mock_re_test_order1004'],
            ],
            [
                'number' => '#1005',
                'customer' => 'jane@example.com',
                'payment_method' => 'bank_transfer',
                'status' => 'pending', 'financial_status' => 'pending', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subHours(2),
                'lines' => [
                    ['product' => 'Leather Belt', 'values' => ['S/M', 'Black'], 'quantity' => 1, 'unit_price' => 3499],
                ],
                'totals' => ['subtotal' => 3499, 'discount' => 0, 'shipping' => 499, 'tax' => 559, 'total' => 3998],
                'payment' => ['reference' => 'mock_test_order1005', 'status' => 'pending'],
            ],
            [
                'number' => '#1006',
                'customer' => 'michael@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product' => 'Running Sneakers', 'values' => ['EU 42', 'Black'], 'quantity' => 1, 'unit_price' => 11999],
                ],
                'totals' => ['subtotal' => 11999, 'discount' => 0, 'shipping' => 499, 'tax' => 1916, 'total' => 12498],
                'payment' => ['reference' => 'mock_test_order1006', 'status' => 'captured'],
            ],
            [
                'number' => '#1007',
                'customer' => 'sarah@example.com',
                'payment_method' => 'paypal',
                'status' => 'fulfilled', 'financial_status' => 'paid', 'fulfillment_status' => 'fulfilled',
                'placed_at' => now()->subDays(20),
                'lines' => [
                    ['product' => 'V-Neck Linen Tee', 'values' => ['M', 'Beige'], 'quantity' => 2, 'unit_price' => 3499],
                    ['product' => 'Wool Scarf', 'values' => ['Grey'], 'quantity' => 1, 'unit_price' => 2999],
                ],
                'totals' => ['subtotal' => 9997, 'discount' => 0, 'shipping' => 499, 'tax' => 1596, 'total' => 10496],
                'payment' => ['reference' => 'mock_test_order1007', 'status' => 'captured'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL1112223334', 'shipped_at' => now()->subDays(18)],
            ],
            [
                'number' => '#1008',
                'customer' => 'david@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'partially_refunded', 'fulfillment_status' => 'fulfilled',
                'placed_at' => now()->subDays(12),
                'lines' => [
                    ['product' => 'Cargo Pants', 'values' => ['32', 'Khaki'], 'quantity' => 1, 'unit_price' => 5499],
                    ['product' => 'Graphic Print Tee', 'values' => ['L'], 'quantity' => 1, 'unit_price' => 2999],
                ],
                'totals' => ['subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997],
                'payment' => ['reference' => 'mock_test_order1008', 'status' => 'captured'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'UPS', 'tracking_number' => 'UPS5556667778', 'shipped_at' => now()->subDays(10)],
                'refund' => ['amount' => 2999, 'reason' => 'Item returned', 'reference' => 'mock_re_test_order1008'],
            ],
            [
                'number' => '#1009',
                'customer' => 'emma@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDays(3),
                'lines' => [
                    ['product' => 'Canvas Tote Bag', 'values' => ['Natural'], 'quantity' => 1, 'unit_price' => 1999],
                    ['product' => 'Bucket Hat', 'values' => ['S/M', 'Black'], 'quantity' => 1, 'unit_price' => 2499],
                ],
                'totals' => ['subtotal' => 4498, 'discount' => 0, 'shipping' => 499, 'tax' => 718, 'total' => 4997],
                'payment' => ['reference' => 'mock_test_order1009', 'status' => 'captured'],
            ],
            [
                'number' => '#1010',
                'customer' => 'customer@acme.test',
                'payment_method' => 'paypal',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product' => 'Cashmere Overcoat', 'values' => ['M', 'Camel'], 'quantity' => 1, 'unit_price' => 49999],
                ],
                'totals' => ['subtotal' => 49999, 'discount' => 0, 'shipping' => 499, 'tax' => 7983, 'total' => 50498],
                'payment' => ['reference' => 'mock_test_order1010', 'status' => 'captured'],
            ],
            [
                'number' => '#1011',
                'customer' => 'james@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'fulfilled',
                'placed_at' => now()->subDays(25),
                'lines' => [
                    ['product' => 'Striped Polo Shirt', 'values' => ['XL'], 'quantity' => 1, 'unit_price' => 2799],
                ],
                'totals' => ['subtotal' => 2799, 'discount' => 0, 'shipping' => 499, 'tax' => 447, 'total' => 3298],
                'payment' => ['reference' => 'mock_test_order1011', 'status' => 'captured'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'FedEx', 'tracking_number' => 'FX9998887776', 'shipped_at' => now()->subDays(23)],
            ],
            [
                'number' => '#1012',
                'customer' => 'lisa@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDays(4),
                'lines' => [
                    ['product' => 'Chino Shorts', 'values' => ['34', 'Navy'], 'quantity' => 2, 'unit_price' => 3999],
                ],
                'totals' => ['subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497],
                'payment' => ['reference' => 'mock_test_order1012', 'status' => 'captured'],
            ],
            [
                'number' => '#1013',
                'customer' => 'robert@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product' => 'Wide Leg Trousers', 'values' => ['M'], 'quantity' => 1, 'unit_price' => 4999],
                    ['product' => 'Wool Scarf', 'values' => ['Burgundy'], 'quantity' => 1, 'unit_price' => 2999],
                ],
                'totals' => ['subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497],
                'payment' => ['reference' => 'mock_test_order1013', 'status' => 'captured'],
            ],
            [
                'number' => '#1014',
                'customer' => 'anna@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'fulfilled',
                'placed_at' => now()->subDays(14),
                'lines' => [
                    ['product' => 'Gift Card', 'values' => ['50 EUR'], 'quantity' => 1, 'unit_price' => 5000],
                ],
                'totals' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 5000],
                'payment' => ['reference' => 'mock_test_order1014', 'status' => 'captured'],
                'fulfillment' => ['status' => 'delivered', 'shipped_at' => now()->subDays(14), 'delivered_at' => now()->subDays(14)],
            ],
            [
                'number' => '#1015',
                'customer' => 'customer@acme.test',
                'payment_method' => 'bank_transfer',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now(),
                'lines' => [
                    ['product' => 'Classic Cotton T-Shirt', 'values' => ['M', 'White'], 'quantity' => 1, 'unit_price' => 2499, 'discount' => 250],
                    ['product' => 'Graphic Print Tee', 'values' => ['M'], 'quantity' => 1, 'unit_price' => 2999, 'discount' => 300],
                ],
                'totals' => ['subtotal' => 5498, 'discount' => 550, 'shipping' => 499, 'tax' => 790, 'total' => 5447],
                'payment' => ['reference' => 'mock_test_order1015', 'status' => 'captured'],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function electronicsOrders(): array
    {
        return [
            [
                'number' => '#5001',
                'customer' => 'techfan@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'fulfilled',
                'placed_at' => now()->subDays(6),
                'lines' => [
                    ['product' => 'Pro Laptop 15', 'values' => ['512GB'], 'quantity' => 1, 'unit_price' => 119999],
                    ['product' => 'USB-C Cable 2m', 'quantity' => 1, 'unit_price' => 1299],
                ],
                'totals' => ['subtotal' => 121298, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 121298],
                'payment' => ['reference' => 'mock_test_order5001', 'status' => 'captured'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL5550001112', 'shipped_at' => now()->subDays(4)],
            ],
            [
                'number' => '#5002',
                'customer' => 'gadgetlover@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDays(2),
                'lines' => [
                    ['product' => 'Wireless Headphones', 'values' => ['Black'], 'quantity' => 1, 'unit_price' => 14999],
                ],
                'totals' => ['subtotal' => 14999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 14999],
                'payment' => ['reference' => 'mock_test_order5002', 'status' => 'captured'],
            ],
            [
                'number' => '#5003',
                'customer' => 'techfan@example.com',
                'payment_method' => 'bank_transfer',
                'status' => 'pending', 'financial_status' => 'pending', 'fulfillment_status' => 'unfulfilled',
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product' => 'Monitor Stand', 'quantity' => 1, 'unit_price' => 4999],
                ],
                'totals' => ['subtotal' => 4999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 4999],
                'payment' => ['reference' => 'mock_test_order5003', 'status' => 'pending'],
            ],
        ];
    }
}
