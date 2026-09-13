<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ($this->fashionOrders() as $definition) {
            $this->seedOrder($fashion, $definition);
        }

        foreach ($this->electronicsOrders() as $definition) {
            $this->seedOrder($electronics, $definition);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function seedOrder(Store $store, array $definition): void
    {
        $customer = Customer::query()
            ->where('store_id', $store->getKey())
            ->where('email', $definition['customer'])
            ->firstOrFail();

        $defaultAddress = $customer->customerAddresses()->where('is_default', true)->first()
            ?? $customer->customerAddresses()->firstOrFail();

        $order = Order::query()->updateOrCreate(
            ['order_number' => $definition['number']],
            [
                'store_id' => $store->getKey(),
                'customer_id' => $customer->getKey(),
                'payment_method' => $definition['method'],
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
                'billing_address_json' => $defaultAddress->address_json,
                'shipping_address_json' => $defaultAddress->address_json,
                'placed_at' => $definition['placed_at'],
            ]
        );

        foreach ($order->fulfillments()->pluck('id')->all() as $fulfillmentId) {
            FulfillmentLine::query()->where('fulfillment_id', $fulfillmentId)->delete();
        }

        $order->fulfillments()->delete();
        $order->orderLines()->delete();

        $lines = [];

        foreach ($definition['lines'] as $lineIndex => $line) {
            $variant = $this->findVariant($store, $line['handle'], $line['values']);
            $product = $variant->product;

            $lines[$lineIndex] = OrderLine::query()->create([
                'order_id' => $order->getKey(),
                'product_id' => $product->getKey(),
                'variant_id' => $variant->getKey(),
                'title_snapshot' => $product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $line['qty'],
                'unit_price_amount' => $line['unit'],
                'total_amount' => $line['qty'] * $line['unit'],
                'tax_lines_json' => [],
                'discount_allocations_json' => $this->allocations($store, $line['discount'] ?? null),
            ]);
        }

        $payment = Payment::query()->updateOrCreate(
            ['provider_payment_id' => $definition['payment_id']],
            [
                'order_id' => $order->getKey(),
                'provider' => PaymentProvider::Mock,
                'method' => $definition['method'],
                'status' => $definition['payment_status'],
                'amount' => $definition['total'],
                'currency' => 'EUR',
            ]
        );

        foreach ($definition['fulfillments'] as $fulfillmentData) {
            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->getKey(),
                'status' => $fulfillmentData['status'],
                'tracking_company' => $fulfillmentData['company'] ?? null,
                'tracking_number' => $fulfillmentData['number'] ?? null,
                'tracking_url' => isset($fulfillmentData['number'])
                    ? 'https://tracking.example.com/'.$fulfillmentData['number']
                    : null,
                'shipped_at' => $fulfillmentData['shipped_at'] ?? $definition['placed_at'],
            ]);

            foreach ($fulfillmentData['lines'] as $lineIndex => $quantity) {
                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->getKey(),
                    'order_line_id' => $lines[$lineIndex]->getKey(),
                    'quantity' => $quantity,
                ]);
            }
        }

        foreach ($definition['refunds'] as $refundData) {
            Refund::query()->updateOrCreate(
                ['provider_refund_id' => $refundData['provider_refund_id']],
                [
                    'order_id' => $order->getKey(),
                    'payment_id' => $payment->getKey(),
                    'amount' => $refundData['amount'],
                    'reason' => $refundData['reason'],
                    'status' => RefundStatus::Processed,
                ]
            );
        }
    }

    /**
     * @param  array<int, string>  $values
     */
    protected function findVariant(Store $store, string $handle, array $values): ProductVariant
    {
        $product = Product::query()
            ->where('store_id', $store->getKey())
            ->where('handle', $handle)
            ->firstOrFail();

        $query = $product->productVariants();

        foreach ($values as $value) {
            $query->whereHas('optionValues', fn ($q) => $q->where('value', $value));
        }

        return $query->firstOrFail();
    }

    /**
     * @return array<int, array<string, int>>
     */
    protected function allocations(Store $store, ?array $discount): array
    {
        if ($discount === null) {
            return [];
        }

        $model = Discount::query()
            ->where('store_id', $store->getKey())
            ->where('code', $discount['code'])
            ->firstOrFail();

        return [['discount_id' => $model->getKey(), 'amount' => $discount['amount']]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fashionOrders(): array
    {
        $card = PaymentMethod::CreditCard;

        return [
            [
                'number' => '#1001', 'customer' => 'customer@acme.test', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDays(2),
                'lines' => [
                    ['handle' => 'classic-cotton-t-shirt', 'values' => ['S', 'White'], 'qty' => 2, 'unit' => 2499],
                ],
                'subtotal' => 4998, 'discount' => 0, 'shipping' => 499, 'tax' => 798, 'total' => 5497,
                'payment_id' => 'mock_test_order1001', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1002', 'customer' => 'customer@acme.test', 'method' => $card,
                'status' => OrderStatus::Fulfilled, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled,
                'placed_at' => now()->subDays(10),
                'lines' => [
                    ['handle' => 'organic-hoodie', 'values' => ['M'], 'qty' => 1, 'unit' => 5999],
                    ['handle' => 'classic-cotton-t-shirt', 'values' => ['L', 'Black'], 'qty' => 1, 'unit' => 2499],
                ],
                'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
                'payment_id' => 'mock_test_order1002', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Delivered, 'company' => 'DHL', 'number' => 'DHL1234567890', 'shipped_at' => now()->subDays(8), 'lines' => [0 => 1, 1 => 1]],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1003', 'customer' => 'jane@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Partial,
                'placed_at' => now()->subDays(5),
                'lines' => [
                    ['handle' => 'premium-slim-fit-jeans', 'values' => ['32', 'Blue'], 'qty' => 1, 'unit' => 7999],
                    ['handle' => 'leather-belt', 'values' => ['L/XL', 'Brown'], 'qty' => 1, 'unit' => 3499],
                ],
                'subtotal' => 11498, 'discount' => 0, 'shipping' => 499, 'tax' => 1836, 'total' => 11997,
                'payment_id' => 'mock_test_order1003', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Shipped, 'company' => 'DHL', 'number' => 'DHL9876543210', 'shipped_at' => now()->subDays(3), 'lines' => [0 => 1]],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1004', 'customer' => 'customer@acme.test', 'method' => $card,
                'status' => OrderStatus::Cancelled, 'financial' => FinancialStatus::Refunded, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDays(15),
                'lines' => [
                    ['handle' => 'classic-cotton-t-shirt', 'values' => ['M', 'Navy'], 'qty' => 1, 'unit' => 2499],
                ],
                'subtotal' => 2499, 'discount' => 0, 'shipping' => 499, 'tax' => 399, 'total' => 2998,
                'payment_id' => 'mock_test_order1004', 'payment_status' => PaymentStatus::Refunded,
                'fulfillments' => [],
                'refunds' => [
                    ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'provider_refund_id' => 'mock_re_test_order1004'],
                ],
            ],
            [
                'number' => '#1005', 'customer' => 'jane@example.com', 'method' => PaymentMethod::BankTransfer,
                'status' => OrderStatus::Pending, 'financial' => FinancialStatus::Pending, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subHours(2),
                'lines' => [
                    ['handle' => 'leather-belt', 'values' => ['S/M', 'Black'], 'qty' => 1, 'unit' => 3499],
                ],
                'subtotal' => 3499, 'discount' => 0, 'shipping' => 499, 'tax' => 559, 'total' => 3998,
                'payment_id' => 'mock_test_order1005', 'payment_status' => PaymentStatus::Pending,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1006', 'customer' => 'michael@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['handle' => 'running-sneakers', 'values' => ['EU 42', 'Black'], 'qty' => 1, 'unit' => 11999],
                ],
                'subtotal' => 11999, 'discount' => 0, 'shipping' => 499, 'tax' => 1916, 'total' => 12498,
                'payment_id' => 'mock_test_order1006', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1007', 'customer' => 'sarah@example.com', 'method' => PaymentMethod::Paypal,
                'status' => OrderStatus::Fulfilled, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled,
                'placed_at' => now()->subDays(20),
                'lines' => [
                    ['handle' => 'v-neck-linen-tee', 'values' => ['M', 'Beige'], 'qty' => 2, 'unit' => 3499],
                    ['handle' => 'wool-scarf', 'values' => ['Grey'], 'qty' => 1, 'unit' => 2999],
                ],
                'subtotal' => 9997, 'discount' => 0, 'shipping' => 499, 'tax' => 1596, 'total' => 10496,
                'payment_id' => 'mock_test_order1007', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Delivered, 'company' => 'DHL', 'number' => 'DHL1112223334', 'shipped_at' => now()->subDays(18), 'lines' => [0 => 2, 1 => 1]],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1008', 'customer' => 'david@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::PartiallyRefunded, 'fulfillment' => FulfillmentStatus::Fulfilled,
                'placed_at' => now()->subDays(12),
                'lines' => [
                    ['handle' => 'cargo-pants', 'values' => ['32', 'Khaki'], 'qty' => 1, 'unit' => 5499],
                    ['handle' => 'graphic-print-tee', 'values' => ['L'], 'qty' => 1, 'unit' => 2999],
                ],
                'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
                'payment_id' => 'mock_test_order1008', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Delivered, 'company' => 'UPS', 'number' => 'UPS5556667778', 'shipped_at' => now()->subDays(10), 'lines' => [0 => 1, 1 => 1]],
                ],
                'refunds' => [
                    ['amount' => 2999, 'reason' => 'Item returned', 'provider_refund_id' => 'mock_re_test_order1008'],
                ],
            ],
            [
                'number' => '#1009', 'customer' => 'emma@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDays(3),
                'lines' => [
                    ['handle' => 'canvas-tote-bag', 'values' => ['Natural'], 'qty' => 1, 'unit' => 1999],
                    ['handle' => 'bucket-hat', 'values' => ['S/M', 'Black'], 'qty' => 1, 'unit' => 2499],
                ],
                'subtotal' => 4498, 'discount' => 0, 'shipping' => 499, 'tax' => 718, 'total' => 4997,
                'payment_id' => 'mock_test_order1009', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1010', 'customer' => 'customer@acme.test', 'method' => PaymentMethod::Paypal,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['handle' => 'cashmere-overcoat', 'values' => ['M', 'Camel'], 'qty' => 1, 'unit' => 49999],
                ],
                'subtotal' => 49999, 'discount' => 0, 'shipping' => 499, 'tax' => 7983, 'total' => 50498,
                'payment_id' => 'mock_test_order1010', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1011', 'customer' => 'james@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled,
                'placed_at' => now()->subDays(25),
                'lines' => [
                    ['handle' => 'striped-polo-shirt', 'values' => ['XL'], 'qty' => 1, 'unit' => 2799],
                ],
                'subtotal' => 2799, 'discount' => 0, 'shipping' => 499, 'tax' => 447, 'total' => 3298,
                'payment_id' => 'mock_test_order1011', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Delivered, 'company' => 'FedEx', 'number' => 'FX9998887776', 'shipped_at' => now()->subDays(23), 'lines' => [0 => 1]],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1012', 'customer' => 'lisa@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDays(4),
                'lines' => [
                    ['handle' => 'chino-shorts', 'values' => ['34', 'Navy'], 'qty' => 2, 'unit' => 3999],
                ],
                'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
                'payment_id' => 'mock_test_order1012', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1013', 'customer' => 'robert@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['handle' => 'wide-leg-trousers', 'values' => ['M'], 'qty' => 1, 'unit' => 4999],
                    ['handle' => 'wool-scarf', 'values' => ['Burgundy'], 'qty' => 1, 'unit' => 2999],
                ],
                'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
                'payment_id' => 'mock_test_order1013', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#1014', 'customer' => 'anna@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled,
                'placed_at' => now()->subDays(14),
                'lines' => [
                    ['handle' => 'gift-card', 'values' => ['50 EUR'], 'qty' => 1, 'unit' => 5000],
                ],
                'subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 5000,
                'payment_id' => 'mock_test_order1014', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Delivered, 'shipped_at' => now()->subDays(14), 'lines' => [0 => 1]],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1015', 'customer' => 'customer@acme.test', 'method' => PaymentMethod::BankTransfer,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now(),
                'lines' => [
                    ['handle' => 'classic-cotton-t-shirt', 'values' => ['M', 'White'], 'qty' => 1, 'unit' => 2499, 'discount' => ['code' => 'WELCOME10', 'amount' => 250]],
                    ['handle' => 'graphic-print-tee', 'values' => ['M'], 'qty' => 1, 'unit' => 2999, 'discount' => ['code' => 'WELCOME10', 'amount' => 300]],
                ],
                'subtotal' => 5498, 'discount' => 550, 'shipping' => 499, 'tax' => 790, 'total' => 5447,
                'payment_id' => 'mock_test_order1015', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function electronicsOrders(): array
    {
        $card = PaymentMethod::CreditCard;

        return [
            [
                'number' => '#5001', 'customer' => 'techfan@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled,
                'placed_at' => now()->subDays(6),
                'lines' => [
                    ['handle' => 'pro-laptop-15', 'values' => ['512GB'], 'qty' => 1, 'unit' => 119999],
                    ['handle' => 'usb-c-cable-2m', 'values' => [], 'qty' => 1, 'unit' => 1299],
                ],
                'subtotal' => 121298, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 121298,
                'payment_id' => 'mock_test_order5001', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [
                    ['status' => FulfillmentShipmentStatus::Delivered, 'company' => 'DHL', 'number' => 'DHL5001001001', 'shipped_at' => now()->subDays(4), 'lines' => [0 => 1, 1 => 1]],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#5002', 'customer' => 'gadgetlover@example.com', 'method' => $card,
                'status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subDays(3),
                'lines' => [
                    ['handle' => 'wireless-headphones', 'values' => ['Black'], 'qty' => 1, 'unit' => 14999],
                ],
                'subtotal' => 14999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 14999,
                'payment_id' => 'mock_test_order5002', 'payment_status' => PaymentStatus::Captured,
                'fulfillments' => [], 'refunds' => [],
            ],
            [
                'number' => '#5003', 'customer' => 'techfan@example.com', 'method' => PaymentMethod::BankTransfer,
                'status' => OrderStatus::Pending, 'financial' => FinancialStatus::Pending, 'fulfillment' => FulfillmentStatus::Unfulfilled,
                'placed_at' => now()->subHours(5),
                'lines' => [
                    ['handle' => 'monitor-stand', 'values' => [], 'qty' => 1, 'unit' => 4999],
                ],
                'subtotal' => 4999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 4999,
                'payment_id' => 'mock_test_order5003', 'payment_status' => PaymentStatus::Pending,
                'fulfillments' => [], 'refunds' => [],
            ],
        ];
    }
}
