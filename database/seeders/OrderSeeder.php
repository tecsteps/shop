<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Create the demo orders with lines, payments, fulfillments, and refunds
     * (spec 07 §3.13). Order numbers are set explicitly - Acme Fashion starts
     * at #1001, Acme Electronics at #5001 per its store settings.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            foreach ($this->fashionOrders() as $definition) {
                $this->seedOrder($fashion, $definition);
            }

            foreach ($this->electronicsOrders() as $definition) {
                $this->seedOrder($electronics, $definition);
            }
        });
    }

    /**
     * Create or refresh one order and its child records.
     *
     * @param  array<string, mixed>  $definition
     */
    private function seedOrder(Store $store, array $definition): void
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', $definition['customer'])
            ->firstOrFail();

        $address = $customer->addresses()->where('is_default', true)->firstOrFail()->address_json;

        $order = Order::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'order_number' => $definition['number']],
            [
                'customer_id' => $customer->id,
                'payment_method' => $definition['payment_method'],
                'status' => $definition['status'],
                'financial_status' => $definition['financial'],
                'fulfillment_status' => $definition['fulfillment'],
                'currency' => 'EUR',
                'subtotal_amount' => $definition['subtotal'],
                'discount_amount' => $definition['discount'],
                'shipping_amount' => $definition['shipping'],
                'tax_amount' => $definition['tax'],
                'total_amount' => $definition['total'],
                'email' => $customer->email,
                'billing_address_json' => $address,
                'shipping_address_json' => $address,
                'placed_at' => $definition['placed_at'](),
            ],
        );

        $this->wipeChildren($order);

        $lines = $this->seedLines($store, $order, $definition['lines']);

        $payment = $order->payments()->create([
            'provider' => 'mock',
            'method' => $definition['payment_method'],
            'provider_payment_id' => $definition['payment']['id'],
            'status' => $definition['payment']['status'],
            'amount' => $definition['total'],
            'currency' => 'EUR',
            'raw_json_encrypted' => null,
        ]);

        if (isset($definition['refund'])) {
            $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $definition['refund']['amount'],
                'reason' => $definition['refund']['reason'],
                'status' => RefundStatus::Processed,
                'provider_refund_id' => $definition['refund']['id'],
            ]);
        }

        if (isset($definition['fulfillment_record'])) {
            $this->seedFulfillment($order, $lines, $definition['fulfillment_record']);
        }
    }

    /**
     * Delete the order's child records so re-seeding stays exact.
     */
    private function wipeChildren(Order $order): void
    {
        $order->refunds()->delete();
        $order->payments()->delete();

        foreach ($order->fulfillments as $fulfillment) {
            $fulfillment->lines()->delete();
            $fulfillment->delete();
        }

        $order->lines()->delete();
    }

    /**
     * Create the order lines with product/SKU snapshots taken from the
     * catalog records seeded earlier (spec 07 §6 "Order Line Snapshots").
     *
     * @param  list<array<string, mixed>>  $lineDefinitions
     * @return list<\App\Models\OrderLine>
     */
    private function seedLines(Store $store, Order $order, array $lineDefinitions): array
    {
        $lines = [];

        foreach ($lineDefinitions as $lineDefinition) {
            $variant = $this->findVariant($store, $lineDefinition['product'], $lineDefinition['options'] ?? []);

            $total = $lineDefinition['qty'] * $lineDefinition['unit'];
            $lineDiscount = $lineDefinition['line_discount'] ?? 0;
            $taxAmount = (int) round(($total - $lineDiscount) * 19 / 119);

            $lines[] = $order->lines()->create([
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'title_snapshot' => $variant->product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $lineDefinition['qty'],
                'unit_price_amount' => $lineDefinition['unit'],
                'total_amount' => $total,
                'tax_lines_json' => [['title' => 'Tax', 'rate' => 1900, 'amount' => $taxAmount]],
                'discount_allocations_json' => $lineDiscount > 0
                    ? [['discount_id' => $this->discountId($store, 'WELCOME10'), 'amount' => $lineDiscount]]
                    : [],
            ]);
        }

        return $lines;
    }

    /**
     * Find a variant by product handle and its option value names.
     *
     * @param  list<string>  $optionValues
     */
    private function findVariant(Store $store, string $productHandle, array $optionValues): ProductVariant
    {
        $product = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', $productHandle)
            ->firstOrFail();

        $wanted = collect($optionValues)
            ->map(fn (string $value): string => mb_strtolower(trim($value)))
            ->sort()
            ->values();

        return $product->variants()
            ->with('optionValues')
            ->get()
            ->first(function (ProductVariant $variant) use ($wanted): bool {
                $actual = $variant->optionValues
                    ->pluck('value')
                    ->map(fn (string $value): string => mb_strtolower(trim($value)))
                    ->sort()
                    ->values();

                return $actual->all() === $wanted->all();
            }) ?? $product->variants()->where('is_default', true)->firstOrFail();
    }

    /**
     * Resolve a discount id by code.
     */
    private function discountId(Store $store, string $code): int
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('code', $code)
            ->firstOrFail()
            ->id;
    }

    /**
     * Create the fulfillment and its lines for an order.
     *
     * @param  list<\App\Models\OrderLine>  $lines
     * @param  array<string, mixed>  $definition
     */
    private function seedFulfillment(Order $order, array $lines, array $definition): void
    {
        $fulfillment = $order->fulfillments()->create([
            'status' => $definition['status'],
            'tracking_company' => $definition['company'] ?? null,
            'tracking_number' => $definition['number'] ?? null,
            'tracking_url' => isset($definition['number'])
                ? 'https://tracking.example.com/'.$definition['number']
                : null,
            'shipped_at' => $definition['shipped_at'](),
        ]);

        $coveredLines = $definition['lines'] === 'all'
            ? $lines
            : array_intersect_key($lines, array_flip($definition['lines']));

        foreach ($coveredLines as $line) {
            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }
    }

    /**
     * The 15 Acme Fashion orders (spec 07 §3.13).
     *
     * @return list<array<string, mixed>>
     */
    private function fashionOrders(): array
    {
        $captured = fn (string $id): array => ['id' => $id, 'status' => PaymentStatus::Captured];

        return [
            [
                'number' => '#1001',
                'customer' => 'customer@acme.test',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(2)->toImmutable(),
                'lines' => [
                    ['product' => 'classic-cotton-t-shirt', 'options' => ['S', 'White'], 'qty' => 2, 'unit' => 2499],
                ],
                'subtotal' => 4998, 'discount' => 0, 'shipping' => 499, 'tax' => 798, 'total' => 5497,
                'payment' => $captured('mock_test_order1001'),
            ],
            [
                'number' => '#1002',
                'customer' => 'customer@acme.test',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Fulfilled,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Fulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(10)->toImmutable(),
                'lines' => [
                    ['product' => 'organic-hoodie', 'options' => ['M'], 'qty' => 1, 'unit' => 5999],
                    ['product' => 'classic-cotton-t-shirt', 'options' => ['L', 'Black'], 'qty' => 1, 'unit' => 2499],
                ],
                'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
                'payment' => $captured('mock_test_order1002'),
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Delivered,
                    'company' => 'DHL',
                    'number' => 'DHL1234567890',
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(8)->toImmutable(),
                    'lines' => 'all',
                ],
            ],
            [
                'number' => '#1003',
                'customer' => 'jane@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Partial,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(5)->toImmutable(),
                'lines' => [
                    ['product' => 'premium-slim-fit-jeans', 'options' => ['32', 'Blue'], 'qty' => 1, 'unit' => 7999],
                    ['product' => 'leather-belt', 'options' => ['L/XL', 'Brown'], 'qty' => 1, 'unit' => 3499],
                ],
                'subtotal' => 11498, 'discount' => 0, 'shipping' => 499, 'tax' => 1836, 'total' => 11997,
                'payment' => $captured('mock_test_order1003'),
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Shipped,
                    'company' => 'DHL',
                    'number' => 'DHL9876543210',
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(3)->toImmutable(),
                    'lines' => [0],
                ],
            ],
            [
                'number' => '#1004',
                'customer' => 'customer@acme.test',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Cancelled,
                'financial' => FinancialStatus::Refunded,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(15)->toImmutable(),
                'lines' => [
                    ['product' => 'classic-cotton-t-shirt', 'options' => ['M', 'Navy'], 'qty' => 1, 'unit' => 2499],
                ],
                'subtotal' => 2499, 'discount' => 0, 'shipping' => 499, 'tax' => 399, 'total' => 2998,
                'payment' => ['id' => 'mock_test_order1004', 'status' => PaymentStatus::Refunded],
                'refund' => ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'id' => 'mock_re_test_order1004'],
            ],
            [
                'number' => '#1005',
                'customer' => 'jane@example.com',
                'payment_method' => PaymentMethod::BankTransfer,
                'status' => OrderStatus::Pending,
                'financial' => FinancialStatus::Pending,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subHours(2)->toImmutable(),
                'lines' => [
                    ['product' => 'leather-belt', 'options' => ['S/M', 'Black'], 'qty' => 1, 'unit' => 3499],
                ],
                'subtotal' => 3499, 'discount' => 0, 'shipping' => 499, 'tax' => 559, 'total' => 3998,
                'payment' => ['id' => 'mock_test_order1005', 'status' => PaymentStatus::Pending],
            ],
            [
                'number' => '#1006',
                'customer' => 'michael@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDay()->toImmutable(),
                'lines' => [
                    ['product' => 'running-sneakers', 'options' => ['EU 42', 'Black'], 'qty' => 1, 'unit' => 11999],
                ],
                'subtotal' => 11999, 'discount' => 0, 'shipping' => 499, 'tax' => 1916, 'total' => 12498,
                'payment' => $captured('mock_test_order1006'),
            ],
            [
                'number' => '#1007',
                'customer' => 'sarah@example.com',
                'payment_method' => PaymentMethod::Paypal,
                'status' => OrderStatus::Fulfilled,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Fulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(20)->toImmutable(),
                'lines' => [
                    ['product' => 'v-neck-linen-tee', 'options' => ['M', 'Beige'], 'qty' => 2, 'unit' => 3499],
                    ['product' => 'wool-scarf', 'options' => ['Grey'], 'qty' => 1, 'unit' => 2999],
                ],
                'subtotal' => 9997, 'discount' => 0, 'shipping' => 499, 'tax' => 1596, 'total' => 10496,
                'payment' => $captured('mock_test_order1007'),
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Delivered,
                    'company' => 'DHL',
                    'number' => 'DHL1112223334',
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(18)->toImmutable(),
                    'lines' => 'all',
                ],
            ],
            [
                'number' => '#1008',
                'customer' => 'david@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::PartiallyRefunded,
                'fulfillment' => FulfillmentOrderStatus::Fulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(12)->toImmutable(),
                'lines' => [
                    ['product' => 'cargo-pants', 'options' => ['32', 'Khaki'], 'qty' => 1, 'unit' => 5499],
                    ['product' => 'graphic-print-tee', 'options' => ['L'], 'qty' => 1, 'unit' => 2999],
                ],
                'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
                'payment' => $captured('mock_test_order1008'),
                'refund' => ['amount' => 2999, 'reason' => 'Item returned', 'id' => 'mock_re_test_order1008'],
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Delivered,
                    'company' => 'UPS',
                    'number' => 'UPS5556667778',
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(10)->toImmutable(),
                    'lines' => 'all',
                ],
            ],
            [
                'number' => '#1009',
                'customer' => 'emma@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(3)->toImmutable(),
                'lines' => [
                    ['product' => 'canvas-tote-bag', 'options' => ['Natural'], 'qty' => 1, 'unit' => 1999],
                    ['product' => 'bucket-hat', 'options' => ['S/M', 'Black'], 'qty' => 1, 'unit' => 2499],
                ],
                'subtotal' => 4498, 'discount' => 0, 'shipping' => 499, 'tax' => 718, 'total' => 4997,
                'payment' => $captured('mock_test_order1009'),
            ],
            [
                'number' => '#1010',
                'customer' => 'customer@acme.test',
                'payment_method' => PaymentMethod::Paypal,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDay()->toImmutable(),
                'lines' => [
                    ['product' => 'cashmere-overcoat', 'options' => ['M', 'Camel'], 'qty' => 1, 'unit' => 49999],
                ],
                'subtotal' => 49999, 'discount' => 0, 'shipping' => 499, 'tax' => 7983, 'total' => 50498,
                'payment' => $captured('mock_test_order1010'),
            ],
            [
                'number' => '#1011',
                'customer' => 'james@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Fulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(25)->toImmutable(),
                'lines' => [
                    ['product' => 'striped-polo-shirt', 'options' => ['XL'], 'qty' => 1, 'unit' => 2799],
                ],
                'subtotal' => 2799, 'discount' => 0, 'shipping' => 499, 'tax' => 447, 'total' => 3298,
                'payment' => $captured('mock_test_order1011'),
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Delivered,
                    'company' => 'FedEx',
                    'number' => 'FX9998887776',
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(23)->toImmutable(),
                    'lines' => 'all',
                ],
            ],
            [
                'number' => '#1012',
                'customer' => 'lisa@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(4)->toImmutable(),
                'lines' => [
                    ['product' => 'chino-shorts', 'options' => ['34', 'Navy'], 'qty' => 2, 'unit' => 3999],
                ],
                'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
                'payment' => $captured('mock_test_order1012'),
            ],
            [
                'number' => '#1013',
                'customer' => 'robert@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDay()->toImmutable(),
                'lines' => [
                    ['product' => 'wide-leg-trousers', 'options' => ['M'], 'qty' => 1, 'unit' => 4999],
                    ['product' => 'wool-scarf', 'options' => ['Burgundy'], 'qty' => 1, 'unit' => 2999],
                ],
                'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
                'payment' => $captured('mock_test_order1013'),
            ],
            [
                'number' => '#1014',
                'customer' => 'anna@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Fulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(14)->toImmutable(),
                'lines' => [
                    ['product' => 'gift-card', 'options' => ['50 EUR'], 'qty' => 1, 'unit' => 5000],
                ],
                'subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 5000,
                'payment' => $captured('mock_test_order1014'),
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Delivered,
                    'company' => null,
                    'number' => null,
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(14)->toImmutable(),
                    'lines' => 'all',
                ],
            ],
            [
                'number' => '#1015',
                'customer' => 'customer@acme.test',
                'payment_method' => PaymentMethod::BankTransfer,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->toImmutable(),
                'lines' => [
                    ['product' => 'classic-cotton-t-shirt', 'options' => ['M', 'White'], 'qty' => 1, 'unit' => 2499, 'line_discount' => 250],
                    ['product' => 'graphic-print-tee', 'options' => ['M'], 'qty' => 1, 'unit' => 2999, 'line_discount' => 300],
                ],
                'subtotal' => 5498, 'discount' => 550, 'shipping' => 499, 'tax' => 790, 'total' => 5447,
                'payment' => $captured('mock_test_order1015'),
            ],
        ];
    }

    /**
     * The 3 Acme Electronics orders (spec 07 §3.13).
     *
     * @return list<array<string, mixed>>
     */
    private function electronicsOrders(): array
    {
        return [
            [
                'number' => '#5001',
                'customer' => 'techfan@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Fulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(5)->toImmutable(),
                'lines' => [
                    ['product' => 'pro-laptop-15', 'options' => ['512GB'], 'qty' => 1, 'unit' => 119999],
                    ['product' => 'usb-c-cable-2m', 'options' => [], 'qty' => 1, 'unit' => 1299],
                ],
                'subtotal' => 121298, 'discount' => 0, 'shipping' => 0, 'tax' => 19367, 'total' => 121298,
                'payment' => ['id' => 'mock_test_order5001', 'status' => PaymentStatus::Captured],
                'fulfillment_record' => [
                    'status' => FulfillmentShipmentStatus::Delivered,
                    'company' => 'DHL',
                    'number' => 'DHL5001000001',
                    'shipped_at' => fn (): CarbonImmutable => now()->subDays(4)->toImmutable(),
                    'lines' => 'all',
                ],
            ],
            [
                'number' => '#5002',
                'customer' => 'gadgetlover@example.com',
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial' => FinancialStatus::Paid,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subDays(2)->toImmutable(),
                'lines' => [
                    ['product' => 'wireless-headphones', 'options' => ['Black'], 'qty' => 1, 'unit' => 14999],
                ],
                'subtotal' => 14999, 'discount' => 0, 'shipping' => 0, 'tax' => 2395, 'total' => 14999,
                'payment' => ['id' => 'mock_test_order5002', 'status' => PaymentStatus::Captured],
            ],
            [
                'number' => '#5003',
                'customer' => 'techfan@example.com',
                'payment_method' => PaymentMethod::BankTransfer,
                'status' => OrderStatus::Pending,
                'financial' => FinancialStatus::Pending,
                'fulfillment' => FulfillmentOrderStatus::Unfulfilled,
                'placed_at' => fn (): CarbonImmutable => now()->subHour()->toImmutable(),
                'lines' => [
                    ['product' => 'monitor-stand', 'options' => [], 'qty' => 1, 'unit' => 4999],
                ],
                'subtotal' => 4999, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 4999,
                'payment' => ['id' => 'mock_test_order5003', 'status' => PaymentStatus::Pending],
            ],
        ];
    }
}
