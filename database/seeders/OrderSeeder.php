<?php

namespace Database\Seeders;

use App\Models\Customer;
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
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        $this->seedFashionOrders($fashion);
        $this->seedElectronicsOrders($electronics);
    }

    private function seedFashionOrders(Store $store): void
    {
        app()->instance('current_store', $store);

        // Preload all needed customers and products
        $john = Customer::where('email', 'customer@acme.test')->first();
        $jane = Customer::where('email', 'jane@example.com')->first();
        $michael = Customer::where('email', 'michael@example.com')->first();
        $sarah = Customer::where('email', 'sarah@example.com')->first();
        $david = Customer::where('email', 'david@example.com')->first();
        $emma = Customer::where('email', 'emma@example.com')->first();
        $james = Customer::where('email', 'james@example.com')->first();
        $lisa = Customer::where('email', 'lisa@example.com')->first();
        $robert = Customer::where('email', 'robert@example.com')->first();
        $anna = Customer::where('email', 'anna@example.com')->first();

        $johnAddr = $john->addresses()->where('is_default', true)->first();
        $addressJson = $johnAddr ? $johnAddr->address_json : $this->defaultAddress();

        // #1001 - Awaiting fulfillment (Credit Card)
        $this->createOrder($store, $john, [
            'order_number' => '#1001',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(2)->toIso8601String(),
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'option_match' => ['S', 'White'], 'qty' => 2, 'price' => 2499],
            ],
            'subtotal' => 4998,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 798,
            'total' => 5497,
            'payment_id' => 'mock_test_order1001',
            'payment_status' => 'captured',
        ]);

        // #1002 - Fully delivered (Credit Card)
        $order1002 = $this->createOrder($store, $john, [
            'order_number' => '#1002',
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(10)->toIso8601String(),
            'lines' => [
                ['handle' => 'organic-hoodie', 'option_match' => ['M'], 'qty' => 1, 'price' => 5999],
                ['handle' => 'classic-cotton-t-shirt', 'option_match' => ['L', 'Black'], 'qty' => 1, 'price' => 2499],
            ],
            'subtotal' => 8498,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1357,
            'total' => 8997,
            'payment_id' => 'mock_test_order1002',
            'payment_status' => 'captured',
        ]);

        $this->createFulfillment($order1002, 'delivered', 'DHL', 'DHL1234567890', now()->subDays(8), true);

        // #1003 - Partially fulfilled (Credit Card)
        $order1003 = $this->createOrder($store, $jane, [
            'order_number' => '#1003',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'partial',
            'placed_at' => now()->subDays(5)->toIso8601String(),
            'lines' => [
                ['handle' => 'premium-slim-fit-jeans', 'option_match' => ['32', 'Blue'], 'qty' => 1, 'price' => 7999],
                ['handle' => 'leather-belt', 'option_match' => ['L/XL', 'Brown'], 'qty' => 1, 'price' => 3499],
            ],
            'subtotal' => 11498,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1836,
            'total' => 11997,
            'payment_id' => 'mock_test_order1003',
            'payment_status' => 'captured',
        ]);

        // Only fulfill the jeans line
        $this->createPartialFulfillment($order1003, 'shipped', 'DHL', 'DHL9876543210', now()->subDays(3), [0]);

        // #1004 - Cancelled with full refund (Credit Card)
        $order1004 = $this->createOrder($store, $john, [
            'order_number' => '#1004',
            'payment_method' => 'credit_card',
            'status' => 'cancelled',
            'financial_status' => 'refunded',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(15)->toIso8601String(),
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'option_match' => ['M', 'Navy'], 'qty' => 1, 'price' => 2499],
            ],
            'subtotal' => 2499,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 399,
            'total' => 2998,
            'payment_id' => 'mock_test_order1004',
            'payment_status' => 'refunded',
        ]);

        $payment1004 = $order1004->payments()->first();
        Refund::create([
            'order_id' => $order1004->id,
            'payment_id' => $payment1004->id,
            'amount' => 2998,
            'reason' => 'Customer requested cancellation',
            'status' => 'processed',
            'provider_refund_id' => 'mock_re_test_order1004',
            'created_at' => now()->subDays(14)->toIso8601String(),
        ]);

        // #1005 - Bank transfer awaiting payment
        $this->createOrder($store, $jane, [
            'order_number' => '#1005',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subHours(2)->toIso8601String(),
            'lines' => [
                ['handle' => 'leather-belt', 'option_match' => ['S/M', 'Black'], 'qty' => 1, 'price' => 3499],
            ],
            'subtotal' => 3499,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 559,
            'total' => 3998,
            'payment_id' => 'mock_test_order1005',
            'payment_status' => 'pending',
        ]);

        // #1006 - Standard paid order
        $this->createOrder($store, $michael, [
            'order_number' => '#1006',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay()->toIso8601String(),
            'lines' => [
                ['handle' => 'running-sneakers', 'option_match' => ['EU 42', 'Black'], 'qty' => 1, 'price' => 11999],
            ],
            'subtotal' => 11999,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1916,
            'total' => 12498,
            'payment_id' => 'mock_test_order1006',
            'payment_status' => 'captured',
        ]);

        // #1007 - Multi-item delivered (PayPal)
        $order1007 = $this->createOrder($store, $sarah, [
            'order_number' => '#1007',
            'payment_method' => 'paypal',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(20)->toIso8601String(),
            'lines' => [
                ['handle' => 'v-neck-linen-tee', 'option_match' => ['M', 'Beige'], 'qty' => 2, 'price' => 3499],
                ['handle' => 'wool-scarf', 'option_match' => ['Grey'], 'qty' => 1, 'price' => 2999],
            ],
            'subtotal' => 9997,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1596,
            'total' => 10496,
            'payment_id' => 'mock_test_order1007',
            'payment_status' => 'captured',
        ]);

        $this->createFulfillment($order1007, 'delivered', 'DHL', 'DHL1112223334', now()->subDays(18), true);

        // #1008 - Partial refund
        $order1008 = $this->createOrder($store, $david, [
            'order_number' => '#1008',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'partially_refunded',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(12)->toIso8601String(),
            'lines' => [
                ['handle' => 'cargo-pants', 'option_match' => ['32', 'Khaki'], 'qty' => 1, 'price' => 5499],
                ['handle' => 'graphic-print-tee', 'option_match' => ['L'], 'qty' => 1, 'price' => 2999],
            ],
            'subtotal' => 8498,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1357,
            'total' => 8997,
            'payment_id' => 'mock_test_order1008',
            'payment_status' => 'captured',
        ]);

        $this->createFulfillment($order1008, 'delivered', 'UPS', 'UPS5556667778', now()->subDays(10), true);

        $payment1008 = $order1008->payments()->first();
        Refund::create([
            'order_id' => $order1008->id,
            'payment_id' => $payment1008->id,
            'amount' => 2999,
            'reason' => 'Item returned',
            'status' => 'processed',
            'provider_refund_id' => 'mock_re_test_order1008',
            'created_at' => now()->subDays(8)->toIso8601String(),
        ]);

        // #1009 - Accessories order
        $this->createOrder($store, $emma, [
            'order_number' => '#1009',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(3)->toIso8601String(),
            'lines' => [
                ['handle' => 'canvas-tote-bag', 'option_match' => ['Natural'], 'qty' => 1, 'price' => 1999],
                ['handle' => 'bucket-hat', 'option_match' => ['S/M', 'Black'], 'qty' => 1, 'price' => 2499],
            ],
            'subtotal' => 4498,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 718,
            'total' => 4997,
            'payment_id' => 'mock_test_order1009',
            'payment_status' => 'captured',
        ]);

        // #1010 - High-value order (PayPal)
        $this->createOrder($store, $john, [
            'order_number' => '#1010',
            'payment_method' => 'paypal',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay()->toIso8601String(),
            'lines' => [
                ['handle' => 'cashmere-overcoat', 'option_match' => ['M', 'Camel'], 'qty' => 1, 'price' => 49999],
            ],
            'subtotal' => 49999,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 7983,
            'total' => 50498,
            'payment_id' => 'mock_test_order1010',
            'payment_status' => 'captured',
        ]);

        // #1011 - Single item delivered
        $order1011 = $this->createOrder($store, $james, [
            'order_number' => '#1011',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(25)->toIso8601String(),
            'lines' => [
                ['handle' => 'striped-polo-shirt', 'option_match' => ['XL'], 'qty' => 1, 'price' => 2799],
            ],
            'subtotal' => 2799,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 447,
            'total' => 3298,
            'payment_id' => 'mock_test_order1011',
            'payment_status' => 'captured',
        ]);

        $this->createFulfillment($order1011, 'delivered', 'FedEx', 'FX9998887776', now()->subDays(23), true);

        // #1012 - Multi-quantity order
        $this->createOrder($store, $lisa, [
            'order_number' => '#1012',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(4)->toIso8601String(),
            'lines' => [
                ['handle' => 'chino-shorts', 'option_match' => ['34', 'Navy'], 'qty' => 2, 'price' => 3999],
            ],
            'subtotal' => 7998,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1277,
            'total' => 8497,
            'payment_id' => 'mock_test_order1012',
            'payment_status' => 'captured',
        ]);

        // #1013 - Multi-item order
        $this->createOrder($store, $robert, [
            'order_number' => '#1013',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay()->toIso8601String(),
            'lines' => [
                ['handle' => 'wide-leg-trousers', 'option_match' => ['M'], 'qty' => 1, 'price' => 4999],
                ['handle' => 'wool-scarf', 'option_match' => ['Burgundy'], 'qty' => 1, 'price' => 2999],
            ],
            'subtotal' => 7998,
            'discount' => 0,
            'shipping' => 499,
            'tax' => 1277,
            'total' => 8497,
            'payment_id' => 'mock_test_order1013',
            'payment_status' => 'captured',
        ]);

        // #1014 - Digital product order (auto-fulfilled)
        $order1014 = $this->createOrder($store, $anna, [
            'order_number' => '#1014',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(14)->toIso8601String(),
            'lines' => [
                ['handle' => 'gift-card', 'option_match' => ['50 EUR'], 'qty' => 1, 'price' => 5000],
            ],
            'subtotal' => 5000,
            'discount' => 0,
            'shipping' => 0,
            'tax' => 798,
            'total' => 5000,
            'payment_id' => 'mock_test_order1014',
            'payment_status' => 'captured',
        ]);

        $this->createDigitalFulfillment($order1014, now()->subDays(14));

        // #1015 - Order with discount (Bank Transfer, confirmed)
        $this->createOrder($store, $john, [
            'order_number' => '#1015',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->toIso8601String(),
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'option_match' => ['M', 'White'], 'qty' => 1, 'price' => 2499],
                ['handle' => 'graphic-print-tee', 'option_match' => ['M'], 'qty' => 1, 'price' => 2999],
            ],
            'subtotal' => 5498,
            'discount' => 550,
            'shipping' => 499,
            'tax' => 790,
            'total' => 5447,
            'payment_id' => 'mock_test_order1015',
            'payment_status' => 'captured',
        ]);
    }

    private function seedElectronicsOrders(Store $store): void
    {
        app()->instance('current_store', $store);

        $techfan = Customer::where('email', 'techfan@example.com')->first();
        $gadgetlover = Customer::where('email', 'gadgetlover@example.com')->first();

        // #5001
        $order5001 = $this->createOrder($store, $techfan, [
            'order_number' => '#5001',
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(5)->toIso8601String(),
            'lines' => [
                ['handle' => 'pro-laptop-15', 'option_match' => ['512GB'], 'qty' => 1, 'price' => 119999],
                ['handle' => 'usb-c-cable-2m', 'option_match' => [], 'qty' => 1, 'price' => 1299],
            ],
            'subtotal' => 121298,
            'discount' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => 121298,
            'payment_id' => 'mock_test_order5001',
            'payment_status' => 'captured',
        ]);

        $this->createFulfillment($order5001, 'delivered', 'DHL', 'DHL5001000001', now()->subDays(3), true);

        // #5002
        $this->createOrder($store, $gadgetlover, [
            'order_number' => '#5002',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(2)->toIso8601String(),
            'lines' => [
                ['handle' => 'wireless-headphones', 'option_match' => ['Black'], 'qty' => 1, 'price' => 14999],
            ],
            'subtotal' => 14999,
            'discount' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => 14999,
            'payment_id' => 'mock_test_order5002',
            'payment_status' => 'captured',
        ]);

        // #5003 - Bank transfer pending
        $this->createOrder($store, $techfan, [
            'order_number' => '#5003',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay()->toIso8601String(),
            'lines' => [
                ['handle' => 'monitor-stand', 'option_match' => [], 'qty' => 1, 'price' => 4999],
            ],
            'subtotal' => 4999,
            'discount' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => 4999,
            'payment_id' => 'mock_test_order5003',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createOrder(Store $store, Customer $customer, array $data): Order
    {
        $defaultAddr = $customer->addresses()->where('is_default', true)->first();
        $addressJson = $defaultAddr ? $defaultAddr->address_json : $this->defaultAddress();

        $order = Order::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => $data['order_number'],
            'payment_method' => $data['payment_method'],
            'status' => $data['status'],
            'financial_status' => $data['financial_status'],
            'fulfillment_status' => $data['fulfillment_status'],
            'currency' => 'EUR',
            'subtotal_amount' => $data['subtotal'],
            'discount_amount' => $data['discount'],
            'shipping_amount' => $data['shipping'],
            'tax_amount' => $data['tax'],
            'total_amount' => $data['total'],
            'email' => $customer->email,
            'billing_address_json' => $addressJson,
            'shipping_address_json' => $addressJson,
            'placed_at' => $data['placed_at'],
        ]);

        foreach ($data['lines'] as $lineData) {
            $product = Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('handle', $lineData['handle'])
                ->first();

            $variant = $this->findVariant($product, $lineData['option_match']);

            OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $lineData['qty'],
                'unit_price_amount' => $lineData['price'],
                'total_amount' => $lineData['price'] * $lineData['qty'],
                'tax_lines_json' => [],
                'discount_allocations_json' => [],
            ]);
        }

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $data['payment_method'],
            'provider_payment_id' => $data['payment_id'],
            'status' => $data['payment_status'],
            'amount' => $data['total'],
            'currency' => 'EUR',
            'raw_json_encrypted' => null,
            'created_at' => $data['placed_at'],
        ]);

        return $order;
    }

    /**
     * @param  list<string>  $optionMatch
     */
    private function findVariant(Product $product, array $optionMatch): ProductVariant
    {
        if (empty($optionMatch)) {
            return $product->variants()->where('is_default', true)->first()
                ?? $product->variants()->first();
        }

        $variants = $product->variants()->with('optionValues')->get();

        foreach ($variants as $variant) {
            $variantValues = $variant->optionValues->pluck('value')->sort()->values()->toArray();
            $matchValues = collect($optionMatch)->sort()->values()->toArray();

            if ($variantValues === $matchValues) {
                return $variant;
            }
        }

        // Fallback to default variant
        return $product->variants()->where('is_default', true)->first()
            ?? $product->variants()->first();
    }

    private function createFulfillment(Order $order, string $status, string $company, string $trackingNumber, \Carbon\CarbonInterface $shippedAt, bool $allLines): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => $status,
            'tracking_company' => $company,
            'tracking_number' => $trackingNumber,
            'tracking_url' => 'https://tracking.example.com/'.$trackingNumber,
            'shipped_at' => $shippedAt->toIso8601String(),
            'delivered_at' => $status === 'delivered' ? $shippedAt->addDays(2)->toIso8601String() : null,
            'created_at' => $shippedAt->toIso8601String(),
        ]);

        if ($allLines) {
            foreach ($order->lines as $line) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $line->id,
                    'quantity' => $line->quantity,
                ]);
            }
        }
    }

    /**
     * @param  list<int>  $lineIndices
     */
    private function createPartialFulfillment(Order $order, string $status, string $company, string $trackingNumber, \Carbon\CarbonInterface $shippedAt, array $lineIndices): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => $status,
            'tracking_company' => $company,
            'tracking_number' => $trackingNumber,
            'tracking_url' => 'https://tracking.example.com/'.$trackingNumber,
            'shipped_at' => $shippedAt->toIso8601String(),
            'created_at' => $shippedAt->toIso8601String(),
        ]);

        $lines = $order->lines()->get();
        foreach ($lineIndices as $idx) {
            if (isset($lines[$idx])) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $lines[$idx]->id,
                    'quantity' => $lines[$idx]->quantity,
                ]);
            }
        }
    }

    private function createDigitalFulfillment(Order $order, \Carbon\CarbonInterface $placedAt): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => $placedAt->toIso8601String(),
            'delivered_at' => $placedAt->toIso8601String(),
            'created_at' => $placedAt->toIso8601String(),
        ]);

        foreach ($order->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultAddress(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Hauptstrasse 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '10115',
        ];
    }
}
