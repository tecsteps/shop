<?php

namespace Database\Seeders;

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
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Seed orders with lines, payments, fulfillments, and refunds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedAcmeFashionOrders();
            $this->seedAcmeElectronicsOrders();
        });
    }

    private function seedAcmeFashionOrders(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $customers = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get()
            ->keyBy('email');

        $johnAddress = $this->johnAddress();
        $janeAddress = $this->janeAddress();

        foreach ($this->fashionOrderDefinitions() as $def) {
            $customer = $customers[$def['customer_email']];
            $address = $def['customer_email'] === 'customer@acme.test' ? $johnAddress : null;
            $address = $def['customer_email'] === 'jane@example.com' ? $janeAddress : $address;

            if (! $address) {
                $nameParts = explode(' ', $customer->name);
                $customerAddr = $customer->addresses()->where('is_default', true)->first();
                $address = $customerAddr
                    ? $customerAddr->address_json
                    : [
                        'first_name' => $nameParts[0] ?? '',
                        'last_name' => $nameParts[1] ?? '',
                        'company' => '',
                        'address1' => 'Hauptstrasse 1',
                        'address2' => '',
                        'city' => 'Berlin',
                        'province' => '',
                        'province_code' => '',
                        'country' => 'Germany',
                        'country_code' => 'DE',
                        'zip' => '10115',
                        'phone' => '',
                    ];
            }

            $order = Order::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'order_number' => $def['order_number']],
                [
                    'customer_id' => $customer->id,
                    'payment_method' => $def['payment_method'],
                    'status' => $def['status'],
                    'financial_status' => $def['financial_status'],
                    'fulfillment_status' => $def['fulfillment_status'],
                    'currency' => 'EUR',
                    'subtotal_amount' => $def['subtotal'],
                    'discount_amount' => $def['discount'],
                    'shipping_amount' => $def['shipping'],
                    'tax_amount' => $def['tax'],
                    'total_amount' => $def['total'],
                    'email' => $def['customer_email'],
                    'billing_address_json' => $address,
                    'shipping_address_json' => $address,
                    'placed_at' => $def['placed_at'],
                ],
            );

            if ($order->lines()->count() > 0) {
                continue;
            }

            $orderLines = [];
            foreach ($def['lines'] as $lineDef) {
                $product = Product::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('handle', $lineDef['product_handle'])
                    ->firstOrFail();

                $variant = $this->findVariant($product, $lineDef['variant_options']);

                $discountAllocations = [];
                if (isset($lineDef['discount_amount']) && $lineDef['discount_amount'] > 0) {
                    $discount = Discount::withoutGlobalScopes()
                        ->where('store_id', $store->id)
                        ->where('code', $lineDef['discount_code'])
                        ->first();

                    if ($discount) {
                        $discountAllocations = [[
                            'discount_id' => $discount->id,
                            'amount' => $lineDef['discount_amount'],
                        ]];
                    }
                }

                $orderLine = OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $lineDef['quantity'],
                    'unit_price_amount' => $lineDef['unit_price'],
                    'total_amount' => $lineDef['line_total'],
                    'tax_lines_json' => [],
                    'discount_allocations_json' => $discountAllocations,
                ]);

                $orderLines[] = $orderLine;
            }

            $this->createPayment($order, $def);

            if (isset($def['fulfillment'])) {
                $this->createFulfillment($order, $orderLines, $def['fulfillment']);
            }

            if (isset($def['refund'])) {
                $this->createRefund($order, $def['refund']);
            }
        }
    }

    private function seedAcmeElectronicsOrders(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $customers = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get()
            ->keyBy('email');

        $address = [
            'first_name' => 'Tech',
            'last_name' => 'Fan',
            'company' => '',
            'address1' => 'Alexanderplatz 1',
            'address2' => '',
            'city' => 'Berlin',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '10178',
            'phone' => '',
        ];

        $electronicsOrders = [
            [
                'order_number' => '#5001',
                'customer_email' => 'techfan@example.com',
                'payment_method' => 'credit_card',
                'status' => 'fulfilled',
                'financial_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => 121298,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 19376,
                'total' => 121298,
                'placed_at' => now()->subDays(7),
                'lines' => [
                    ['product_handle' => 'pro-laptop-15', 'variant_options' => ['Storage' => '512GB'], 'quantity' => 1, 'unit_price' => 119999, 'line_total' => 119999],
                    ['product_handle' => 'usb-c-cable-2m', 'variant_options' => [], 'quantity' => 1, 'unit_price' => 1299, 'line_total' => 1299],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order5001', 'status' => 'captured', 'method' => 'credit_card'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL5001000001', 'shipped_at' => now()->subDays(5), 'all_lines' => true],
            ],
            [
                'order_number' => '#5002',
                'customer_email' => 'gadgetlover@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 14999,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 2395,
                'total' => 14999,
                'placed_at' => now()->subDays(3),
                'lines' => [
                    ['product_handle' => 'wireless-headphones', 'variant_options' => ['Color' => 'Black'], 'quantity' => 1, 'unit_price' => 14999, 'line_total' => 14999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order5002', 'status' => 'captured', 'method' => 'credit_card'],
            ],
            [
                'order_number' => '#5003',
                'customer_email' => 'techfan@example.com',
                'payment_method' => 'bank_transfer',
                'status' => 'pending',
                'financial_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 4999,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 798,
                'total' => 4999,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product_handle' => 'monitor-stand', 'variant_options' => [], 'quantity' => 1, 'unit_price' => 4999, 'line_total' => 4999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order5003', 'status' => 'pending', 'method' => 'bank_transfer'],
            ],
        ];

        foreach ($electronicsOrders as $def) {
            $customer = $customers[$def['customer_email']];
            $customerAddr = $customer->addresses()->where('is_default', true)->first();
            $orderAddress = $customerAddr ? $customerAddr->address_json : $address;

            $order = Order::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'order_number' => $def['order_number']],
                [
                    'customer_id' => $customer->id,
                    'payment_method' => $def['payment_method'],
                    'status' => $def['status'],
                    'financial_status' => $def['financial_status'],
                    'fulfillment_status' => $def['fulfillment_status'],
                    'currency' => 'EUR',
                    'subtotal_amount' => $def['subtotal'],
                    'discount_amount' => $def['discount'],
                    'shipping_amount' => $def['shipping'],
                    'tax_amount' => $def['tax'],
                    'total_amount' => $def['total'],
                    'email' => $def['customer_email'],
                    'billing_address_json' => $orderAddress,
                    'shipping_address_json' => $orderAddress,
                    'placed_at' => $def['placed_at'],
                ],
            );

            if ($order->lines()->count() > 0) {
                continue;
            }

            $orderLines = [];
            foreach ($def['lines'] as $lineDef) {
                $product = Product::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('handle', $lineDef['product_handle'])
                    ->firstOrFail();

                $variant = $this->findVariant($product, $lineDef['variant_options']);

                $orderLine = OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $lineDef['quantity'],
                    'unit_price_amount' => $lineDef['unit_price'],
                    'total_amount' => $lineDef['line_total'],
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);

                $orderLines[] = $orderLine;
            }

            Payment::firstOrCreate(
                ['order_id' => $order->id, 'provider_payment_id' => $def['payment']['provider_payment_id']],
                [
                    'provider' => 'mock',
                    'method' => $def['payment']['method'],
                    'status' => $def['payment']['status'],
                    'amount' => $def['total'],
                    'currency' => 'EUR',
                ],
            );

            if (isset($def['fulfillment'])) {
                $this->createFulfillment($order, $orderLines, $def['fulfillment']);
            }
        }
    }

    /**
     * Find a variant by its option values.
     *
     * @param  array<string, string>  $optionValues
     */
    private function findVariant(Product $product, array $optionValues): ProductVariant
    {
        if (empty($optionValues)) {
            return $product->variants()->where('is_default', true)->firstOrFail();
        }

        $query = $product->variants();

        foreach ($optionValues as $optionName => $valueName) {
            $query->whereHas('optionValues', function ($q) use ($optionName, $valueName): void {
                $q->where('value', $valueName)
                    ->whereHas('option', function ($q2) use ($optionName): void {
                        $q2->where('name', $optionName);
                    });
            });
        }

        return $query->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function createPayment(Order $order, array $def): void
    {
        $paymentId = $def['payment']['provider_payment_id'] ?? 'mock_test_'.$def['order_number'];

        Payment::firstOrCreate(
            ['order_id' => $order->id, 'provider_payment_id' => $paymentId],
            [
                'provider' => 'mock',
                'method' => $def['payment']['method'] ?? $def['payment_method'],
                'status' => $def['payment']['status'] ?? 'captured',
                'amount' => $def['total'],
                'currency' => 'EUR',
            ],
        );
    }

    /**
     * @param  array<int, OrderLine>  $orderLines
     * @param  array<string, mixed>  $fulfillmentDef
     */
    private function createFulfillment(Order $order, array $orderLines, array $fulfillmentDef): void
    {
        if ($order->fulfillments()->count() > 0) {
            return;
        }

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => $fulfillmentDef['status'],
            'tracking_company' => $fulfillmentDef['tracking_company'] ?? null,
            'tracking_number' => $fulfillmentDef['tracking_number'] ?? null,
            'tracking_url' => isset($fulfillmentDef['tracking_number'])
                ? 'https://tracking.example.com/'.$fulfillmentDef['tracking_number']
                : null,
            'shipped_at' => $fulfillmentDef['shipped_at'] ?? now(),
        ]);

        if ($fulfillmentDef['all_lines'] ?? false) {
            foreach ($orderLines as $orderLine) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLine->id,
                    'quantity' => $orderLine->quantity,
                ]);
            }
        } elseif (isset($fulfillmentDef['line_indices'])) {
            foreach ($fulfillmentDef['line_indices'] as $index) {
                if (isset($orderLines[$index])) {
                    FulfillmentLine::create([
                        'fulfillment_id' => $fulfillment->id,
                        'order_line_id' => $orderLines[$index]->id,
                        'quantity' => $orderLines[$index]->quantity,
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $refundDef
     */
    private function createRefund(Order $order, array $refundDef): void
    {
        if ($order->refunds()->count() > 0) {
            return;
        }

        $payment = $order->payments()->first();

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment?->id,
            'amount' => $refundDef['amount'],
            'reason' => $refundDef['reason'],
            'status' => 'processed',
            'provider_refund_id' => $refundDef['provider_refund_id'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function johnAddress(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => '',
            'address1' => 'Hauptstrasse 1',
            'address2' => '',
            'city' => 'Berlin',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '10115',
            'phone' => '+49 30 12345678',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function janeAddress(): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'company' => '',
            'address1' => 'Schillerstrasse 45',
            'address2' => '',
            'city' => 'Munich',
            'province' => 'Bavaria',
            'province_code' => 'BY',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '80336',
            'phone' => '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fashionOrderDefinitions(): array
    {
        return [
            [
                'order_number' => '#1001',
                'customer_email' => 'customer@acme.test',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 4998,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 798,
                'total' => 5497,
                'placed_at' => now()->subDays(2),
                'lines' => [
                    ['product_handle' => 'classic-cotton-t-shirt', 'variant_options' => ['Size' => 'S', 'Color' => 'White'], 'quantity' => 2, 'unit_price' => 2499, 'line_total' => 4998],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1001', 'status' => 'captured', 'method' => 'credit_card'],
            ],
            [
                'order_number' => '#1002',
                'customer_email' => 'customer@acme.test',
                'payment_method' => 'credit_card',
                'status' => 'fulfilled',
                'financial_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => 8498,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1357,
                'total' => 8997,
                'placed_at' => now()->subDays(10),
                'lines' => [
                    ['product_handle' => 'organic-hoodie', 'variant_options' => ['Size' => 'M'], 'quantity' => 1, 'unit_price' => 5999, 'line_total' => 5999],
                    ['product_handle' => 'classic-cotton-t-shirt', 'variant_options' => ['Size' => 'L', 'Color' => 'Black'], 'quantity' => 1, 'unit_price' => 2499, 'line_total' => 2499],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1002', 'status' => 'captured', 'method' => 'credit_card'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL1234567890', 'shipped_at' => now()->subDays(8), 'all_lines' => true],
            ],
            [
                'order_number' => '#1003',
                'customer_email' => 'jane@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'partial',
                'subtotal' => 11498,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1836,
                'total' => 11997,
                'placed_at' => now()->subDays(5),
                'lines' => [
                    ['product_handle' => 'premium-slim-fit-jeans', 'variant_options' => ['Size' => '32', 'Color' => 'Blue'], 'quantity' => 1, 'unit_price' => 7999, 'line_total' => 7999],
                    ['product_handle' => 'leather-belt', 'variant_options' => ['Size' => 'L/XL', 'Color' => 'Brown'], 'quantity' => 1, 'unit_price' => 3499, 'line_total' => 3499],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1003', 'status' => 'captured', 'method' => 'credit_card'],
                'fulfillment' => ['status' => 'shipped', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL9876543210', 'shipped_at' => now()->subDays(3), 'all_lines' => false, 'line_indices' => [0]],
            ],
            [
                'order_number' => '#1004',
                'customer_email' => 'customer@acme.test',
                'payment_method' => 'credit_card',
                'status' => 'cancelled',
                'financial_status' => 'refunded',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 2499,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 399,
                'total' => 2998,
                'placed_at' => now()->subDays(15),
                'lines' => [
                    ['product_handle' => 'classic-cotton-t-shirt', 'variant_options' => ['Size' => 'M', 'Color' => 'Navy'], 'quantity' => 1, 'unit_price' => 2499, 'line_total' => 2499],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1004', 'status' => 'refunded', 'method' => 'credit_card'],
                'refund' => ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'provider_refund_id' => 'mock_re_test_order1004'],
            ],
            [
                'order_number' => '#1005',
                'customer_email' => 'jane@example.com',
                'payment_method' => 'bank_transfer',
                'status' => 'pending',
                'financial_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 3499,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 559,
                'total' => 3998,
                'placed_at' => now()->subHours(2),
                'lines' => [
                    ['product_handle' => 'leather-belt', 'variant_options' => ['Size' => 'S/M', 'Color' => 'Black'], 'quantity' => 1, 'unit_price' => 3499, 'line_total' => 3499],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1005', 'status' => 'pending', 'method' => 'bank_transfer'],
            ],
            [
                'order_number' => '#1006',
                'customer_email' => 'michael@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 11999,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1916,
                'total' => 12498,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product_handle' => 'running-sneakers', 'variant_options' => ['Size' => 'EU 42', 'Color' => 'Black'], 'quantity' => 1, 'unit_price' => 11999, 'line_total' => 11999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1006', 'status' => 'captured', 'method' => 'credit_card'],
            ],
            [
                'order_number' => '#1007',
                'customer_email' => 'sarah@example.com',
                'payment_method' => 'paypal',
                'status' => 'fulfilled',
                'financial_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => 9997,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1596,
                'total' => 10496,
                'placed_at' => now()->subDays(20),
                'lines' => [
                    ['product_handle' => 'v-neck-linen-tee', 'variant_options' => ['Size' => 'M', 'Color' => 'Beige'], 'quantity' => 2, 'unit_price' => 3499, 'line_total' => 6998],
                    ['product_handle' => 'wool-scarf', 'variant_options' => ['Color' => 'Grey'], 'quantity' => 1, 'unit_price' => 2999, 'line_total' => 2999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1007', 'status' => 'captured', 'method' => 'paypal'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'DHL', 'tracking_number' => 'DHL1112223334', 'shipped_at' => now()->subDays(18), 'all_lines' => true],
            ],
            [
                'order_number' => '#1008',
                'customer_email' => 'david@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'partially_refunded',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => 8498,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1357,
                'total' => 8997,
                'placed_at' => now()->subDays(12),
                'lines' => [
                    ['product_handle' => 'cargo-pants', 'variant_options' => ['Size' => '32', 'Color' => 'Khaki'], 'quantity' => 1, 'unit_price' => 5499, 'line_total' => 5499],
                    ['product_handle' => 'graphic-print-tee', 'variant_options' => ['Size' => 'L'], 'quantity' => 1, 'unit_price' => 2999, 'line_total' => 2999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1008', 'status' => 'captured', 'method' => 'credit_card'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'UPS', 'tracking_number' => 'UPS5556667778', 'shipped_at' => now()->subDays(10), 'all_lines' => true],
                'refund' => ['amount' => 2999, 'reason' => 'Item returned', 'provider_refund_id' => 'mock_re_test_order1008'],
            ],
            [
                'order_number' => '#1009',
                'customer_email' => 'emma@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 4498,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 718,
                'total' => 4997,
                'placed_at' => now()->subDays(3),
                'lines' => [
                    ['product_handle' => 'canvas-tote-bag', 'variant_options' => ['Color' => 'Natural'], 'quantity' => 1, 'unit_price' => 1999, 'line_total' => 1999],
                    ['product_handle' => 'bucket-hat', 'variant_options' => ['Size' => 'S/M', 'Color' => 'Black'], 'quantity' => 1, 'unit_price' => 2499, 'line_total' => 2499],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1009', 'status' => 'captured', 'method' => 'credit_card'],
            ],
            [
                'order_number' => '#1010',
                'customer_email' => 'customer@acme.test',
                'payment_method' => 'paypal',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 49999,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 7983,
                'total' => 50498,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product_handle' => 'cashmere-overcoat', 'variant_options' => ['Size' => 'M', 'Color' => 'Camel'], 'quantity' => 1, 'unit_price' => 49999, 'line_total' => 49999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1010', 'status' => 'captured', 'method' => 'paypal'],
            ],
            [
                'order_number' => '#1011',
                'customer_email' => 'james@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => 2799,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 447,
                'total' => 3298,
                'placed_at' => now()->subDays(25),
                'lines' => [
                    ['product_handle' => 'striped-polo-shirt', 'variant_options' => ['Size' => 'XL'], 'quantity' => 1, 'unit_price' => 2799, 'line_total' => 2799],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1011', 'status' => 'captured', 'method' => 'credit_card'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => 'FedEx', 'tracking_number' => 'FX9998887776', 'shipped_at' => now()->subDays(23), 'all_lines' => true],
            ],
            [
                'order_number' => '#1012',
                'customer_email' => 'lisa@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 7998,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1277,
                'total' => 8497,
                'placed_at' => now()->subDays(4),
                'lines' => [
                    ['product_handle' => 'chino-shorts', 'variant_options' => ['Size' => '34', 'Color' => 'Navy'], 'quantity' => 2, 'unit_price' => 3999, 'line_total' => 7998],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1012', 'status' => 'captured', 'method' => 'credit_card'],
            ],
            [
                'order_number' => '#1013',
                'customer_email' => 'robert@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 7998,
                'discount' => 0,
                'shipping' => 499,
                'tax' => 1277,
                'total' => 8497,
                'placed_at' => now()->subDay(),
                'lines' => [
                    ['product_handle' => 'wide-leg-trousers', 'variant_options' => ['Size' => 'M'], 'quantity' => 1, 'unit_price' => 4999, 'line_total' => 4999],
                    ['product_handle' => 'wool-scarf', 'variant_options' => ['Color' => 'Burgundy'], 'quantity' => 1, 'unit_price' => 2999, 'line_total' => 2999],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1013', 'status' => 'captured', 'method' => 'credit_card'],
            ],
            [
                'order_number' => '#1014',
                'customer_email' => 'anna@example.com',
                'payment_method' => 'credit_card',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'subtotal' => 5000,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 798,
                'total' => 5000,
                'placed_at' => now()->subDays(14),
                'lines' => [
                    ['product_handle' => 'gift-card', 'variant_options' => ['Amount' => '50 EUR'], 'quantity' => 1, 'unit_price' => 5000, 'line_total' => 5000],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1014', 'status' => 'captured', 'method' => 'credit_card'],
                'fulfillment' => ['status' => 'delivered', 'tracking_company' => null, 'tracking_number' => null, 'shipped_at' => now()->subDays(14), 'all_lines' => true],
            ],
            [
                'order_number' => '#1015',
                'customer_email' => 'customer@acme.test',
                'payment_method' => 'bank_transfer',
                'status' => 'paid',
                'financial_status' => 'paid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal' => 5498,
                'discount' => 550,
                'shipping' => 499,
                'tax' => 790,
                'total' => 5447,
                'placed_at' => now(),
                'lines' => [
                    ['product_handle' => 'classic-cotton-t-shirt', 'variant_options' => ['Size' => 'M', 'Color' => 'White'], 'quantity' => 1, 'unit_price' => 2499, 'line_total' => 2499, 'discount_code' => 'WELCOME10', 'discount_amount' => 250],
                    ['product_handle' => 'graphic-print-tee', 'variant_options' => ['Size' => 'M'], 'quantity' => 1, 'unit_price' => 2999, 'line_total' => 2999, 'discount_code' => 'WELCOME10', 'discount_amount' => 300],
                ],
                'payment' => ['provider_payment_id' => 'mock_test_order1015', 'status' => 'captured', 'method' => 'bank_transfer'],
            ],
        ];
    }
}
