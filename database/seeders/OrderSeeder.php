<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
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
        $this->seedAcmeFashionOrders();
        $this->seedAcmeElectronicsOrders();
    }

    private function seedAcmeFashionOrders(): void
    {
        $store = Store::where('handle', 'acme-fashion')->first();
        if (! $store) {
            return;
        }

        // Load customers
        $johnDoe = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'customer@acme.test')->first();
        $janeSmith = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'jane@example.com')->first();
        $michaelBrown = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'michael@example.com')->first();
        $sarahWilson = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'sarah@example.com')->first();
        $davidLee = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'david@example.com')->first();
        $emmaGarcia = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'emma@example.com')->first();
        $jamesTaylor = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'james@example.com')->first();
        $lisaAnderson = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'lisa@example.com')->first();
        $robertMartinez = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'robert@example.com')->first();
        $annaThomas = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'anna@example.com')->first();

        $johnAddress = [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => 'Hauptstrasse 1', 'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
        ];

        $janeAddress = [
            'first_name' => 'Jane', 'last_name' => 'Smith',
            'address1' => 'Schillerstrasse 45', 'city' => 'Munich', 'country_code' => 'DE', 'zip' => '80336',
        ];

        // Helper to find variant by product handle and option values
        $findVariant = function (string $handle, ?string $optionMatch = null) use ($store): ?ProductVariant {
            $product = Product::query()->withoutGlobalScopes()
                ->where('store_id', $store->id)->where('handle', $handle)->first();
            if (! $product) {
                return null;
            }

            $query = ProductVariant::query()->where('product_id', $product->id);

            if ($optionMatch) {
                $query->whereHas('optionValues', function ($q) use ($optionMatch) {
                    $parts = explode(' / ', $optionMatch);
                    $q->whereIn('value', $parts);
                });

                // For multi-option variants, ensure ALL option values match
                $parts = explode(' / ', $optionMatch);
                if (count($parts) > 1) {
                    $query->whereHas('optionValues', fn ($q) => $q->where('value', $parts[0]))
                        ->whereHas('optionValues', fn ($q) => $q->where('value', $parts[1]));
                }
            } else {
                $query->where('is_default', true);
            }

            return $query->first();
        };

        // Order #1001 - Awaiting fulfillment (Credit Card)
        $this->createOrder($store, $johnDoe, '#1001', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDays(2), $johnAddress,
            [['handle' => 'classic-cotton-t-shirt', 'option' => 'S / White', 'qty' => 2, 'price' => 2499]],
            $findVariant, 'mock_test_order1001', PaymentStatus::Captured,
            4998, 0, 499, 798, 5497
        );

        // Order #1002 - Fully delivered (Credit Card)
        $order1002 = $this->createOrder($store, $johnDoe, '#1002', PaymentMethod::CreditCard,
            OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled,
            now()->subDays(10), $johnAddress,
            [
                ['handle' => 'organic-hoodie', 'option' => 'M', 'qty' => 1, 'price' => 5999],
                ['handle' => 'classic-cotton-t-shirt', 'option' => 'L / Black', 'qty' => 1, 'price' => 2499],
            ],
            $findVariant, 'mock_test_order1002', PaymentStatus::Captured,
            8498, 0, 499, 1357, 8997
        );
        $this->createFullFulfillment($order1002, 'DHL', 'DHL1234567890', now()->subDays(8), FulfillmentShipmentStatus::Delivered);

        // Order #1003 - Partially fulfilled (Credit Card)
        $order1003 = $this->createOrder($store, $janeSmith, '#1003', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Partial,
            now()->subDays(5), $janeAddress,
            [
                ['handle' => 'premium-slim-fit-jeans', 'option' => '32 / Blue', 'qty' => 1, 'price' => 7999],
                ['handle' => 'leather-belt', 'option' => 'L/XL / Brown', 'qty' => 1, 'price' => 3499],
            ],
            $findVariant, 'mock_test_order1003', PaymentStatus::Captured,
            11498, 0, 499, 1836, 11997
        );
        // Only the Jeans line fulfilled
        $order1003->loadMissing('lines');
        $jeansLine = $order1003->lines->first();
        if ($jeansLine) {
            $fulfillment1003 = Fulfillment::query()->create([
                'order_id' => $order1003->id,
                'status' => FulfillmentShipmentStatus::Shipped,
                'tracking_company' => 'DHL',
                'tracking_number' => 'DHL9876543210',
                'shipped_at' => now()->subDays(3),
            ]);
            FulfillmentLine::query()->create([
                'fulfillment_id' => $fulfillment1003->id,
                'order_line_id' => $jeansLine->id,
                'quantity' => $jeansLine->quantity,
            ]);
        }

        // Order #1004 - Cancelled with full refund (Credit Card)
        $order1004 = $this->createOrder($store, $johnDoe, '#1004', PaymentMethod::CreditCard,
            OrderStatus::Cancelled, FinancialStatus::Refunded, FulfillmentStatus::Unfulfilled,
            now()->subDays(15), $johnAddress,
            [['handle' => 'classic-cotton-t-shirt', 'option' => 'M / Navy', 'qty' => 1, 'price' => 2499]],
            $findVariant, 'mock_test_order1004', PaymentStatus::Refunded,
            2499, 0, 499, 399, 2998
        );
        $payment1004 = $order1004->payments()->first();
        if ($payment1004) {
            Refund::query()->create([
                'order_id' => $order1004->id,
                'payment_id' => $payment1004->id,
                'amount' => 2998,
                'reason' => 'Customer requested cancellation',
                'status' => RefundStatus::Processed,
                'provider_refund_id' => 'mock_re_test_order1004',
                'created_at' => now()->subDays(14),
            ]);
        }

        // Order #1005 - Bank transfer awaiting payment
        $this->createOrder($store, $janeSmith, '#1005', PaymentMethod::BankTransfer,
            OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled,
            now()->subHours(2), $janeAddress,
            [['handle' => 'leather-belt', 'option' => 'S/M / Black', 'qty' => 1, 'price' => 3499]],
            $findVariant, 'mock_test_order1005', PaymentStatus::Pending,
            3499, 0, 499, 559, 3998
        );

        // Order #1006 - Standard paid order
        $this->createOrder($store, $michaelBrown, '#1006', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDay(), $this->customerAddress($michaelBrown),
            [['handle' => 'running-sneakers', 'option' => 'EU 42 / Black', 'qty' => 1, 'price' => 11999]],
            $findVariant, 'mock_test_order1006', PaymentStatus::Captured,
            11999, 0, 499, 1916, 12498
        );

        // Order #1007 - Multi-item delivered (PayPal)
        $order1007 = $this->createOrder($store, $sarahWilson, '#1007', PaymentMethod::Paypal,
            OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled,
            now()->subDays(20), $this->customerAddress($sarahWilson),
            [
                ['handle' => 'v-neck-linen-tee', 'option' => 'M / Beige', 'qty' => 2, 'price' => 3499],
                ['handle' => 'wool-scarf', 'option' => 'Grey', 'qty' => 1, 'price' => 2999],
            ],
            $findVariant, 'mock_test_order1007', PaymentStatus::Captured,
            9997, 0, 499, 1596, 10496
        );
        $this->createFullFulfillment($order1007, 'DHL', 'DHL1112223334', now()->subDays(18), FulfillmentShipmentStatus::Delivered);

        // Order #1008 - Partial refund (Credit Card)
        $order1008 = $this->createOrder($store, $davidLee, '#1008', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::PartiallyRefunded, FulfillmentStatus::Fulfilled,
            now()->subDays(12), $this->customerAddress($davidLee),
            [
                ['handle' => 'cargo-pants', 'option' => '32 / Khaki', 'qty' => 1, 'price' => 5499],
                ['handle' => 'graphic-print-tee', 'option' => 'L', 'qty' => 1, 'price' => 2999],
            ],
            $findVariant, 'mock_test_order1008', PaymentStatus::Captured,
            8498, 0, 499, 1357, 8997
        );
        $this->createFullFulfillment($order1008, 'UPS', 'UPS5556667778', now()->subDays(10), FulfillmentShipmentStatus::Delivered);
        $payment1008 = $order1008->payments()->first();
        if ($payment1008) {
            Refund::query()->create([
                'order_id' => $order1008->id,
                'payment_id' => $payment1008->id,
                'amount' => 2999,
                'reason' => 'Item returned',
                'status' => RefundStatus::Processed,
                'provider_refund_id' => 'mock_re_test_order1008',
                'created_at' => now()->subDays(8),
            ]);
        }

        // Order #1009 - Accessories order
        $this->createOrder($store, $emmaGarcia, '#1009', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDays(3), $this->customerAddress($emmaGarcia),
            [
                ['handle' => 'canvas-tote-bag', 'option' => 'Natural', 'qty' => 1, 'price' => 1999],
                ['handle' => 'bucket-hat', 'option' => 'S/M / Black', 'qty' => 1, 'price' => 2499],
            ],
            $findVariant, 'mock_test_order1009', PaymentStatus::Captured,
            4498, 0, 499, 718, 4997
        );

        // Order #1010 - High-value order (PayPal)
        $this->createOrder($store, $johnDoe, '#1010', PaymentMethod::Paypal,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDay(), $johnAddress,
            [['handle' => 'cashmere-overcoat', 'option' => 'M / Camel', 'qty' => 1, 'price' => 49999]],
            $findVariant, 'mock_test_order1010', PaymentStatus::Captured,
            49999, 0, 499, 7983, 50498
        );

        // Order #1011 - Single item delivered
        $order1011 = $this->createOrder($store, $jamesTaylor, '#1011', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Fulfilled,
            now()->subDays(25), $this->customerAddress($jamesTaylor),
            [['handle' => 'striped-polo-shirt', 'option' => 'XL', 'qty' => 1, 'price' => 2799]],
            $findVariant, 'mock_test_order1011', PaymentStatus::Captured,
            2799, 0, 499, 447, 3298
        );
        $this->createFullFulfillment($order1011, 'FedEx', 'FX9998887776', now()->subDays(23), FulfillmentShipmentStatus::Delivered);

        // Order #1012 - Multi-quantity order
        $this->createOrder($store, $lisaAnderson, '#1012', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDays(4), $this->customerAddress($lisaAnderson),
            [['handle' => 'chino-shorts', 'option' => '34 / Navy', 'qty' => 2, 'price' => 3999]],
            $findVariant, 'mock_test_order1012', PaymentStatus::Captured,
            7998, 0, 499, 1277, 8497
        );

        // Order #1013 - Multi-item order
        $this->createOrder($store, $robertMartinez, '#1013', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDay(), $this->customerAddress($robertMartinez),
            [
                ['handle' => 'wide-leg-trousers', 'option' => 'M', 'qty' => 1, 'price' => 4999],
                ['handle' => 'wool-scarf', 'option' => 'Burgundy', 'qty' => 1, 'price' => 2999],
            ],
            $findVariant, 'mock_test_order1013', PaymentStatus::Captured,
            7998, 0, 499, 1277, 8497
        );

        // Order #1014 - Digital product order (auto-fulfilled)
        $order1014 = $this->createOrder($store, $annaThomas, '#1014', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Fulfilled,
            now()->subDays(14), $this->customerAddress($annaThomas),
            [['handle' => 'gift-card', 'option' => '50 EUR', 'qty' => 1, 'price' => 5000]],
            $findVariant, 'mock_test_order1014', PaymentStatus::Captured,
            5000, 0, 0, 798, 5000
        );
        // Digital auto-fulfillment
        $this->createFullFulfillment($order1014, null, null, now()->subDays(14), FulfillmentShipmentStatus::Delivered);

        // Order #1015 - Order with discount (Bank Transfer, confirmed)
        $welcome10 = Discount::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)->where('code', 'WELCOME10')->first();

        $order1015 = $this->createOrder($store, $johnDoe, '#1015', PaymentMethod::BankTransfer,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now(), $johnAddress,
            [
                [
                    'handle' => 'classic-cotton-t-shirt', 'option' => 'M / White', 'qty' => 1, 'price' => 2499,
                    'discount_allocations' => $welcome10 ? [['discount_id' => $welcome10->id, 'amount' => 250]] : [],
                ],
                [
                    'handle' => 'graphic-print-tee', 'option' => 'M', 'qty' => 1, 'price' => 2999,
                    'discount_allocations' => $welcome10 ? [['discount_id' => $welcome10->id, 'amount' => 300]] : [],
                ],
            ],
            $findVariant, 'mock_test_order1015', PaymentStatus::Captured,
            5498, 550, 499, 790, 5447
        );
    }

    /**
     * @param  array<int, array{handle: string, option?: string|null, qty: int, price: int, discount_allocations?: array<int, array<string, mixed>>}>  $lines
     */
    private function createOrder(
        Store $store,
        ?Customer $customer,
        string $orderNumber,
        PaymentMethod $paymentMethod,
        OrderStatus $orderStatus,
        FinancialStatus $financialStatus,
        FulfillmentStatus $fulfillmentStatus,
        \Illuminate\Support\Carbon $placedAt,
        array $address,
        array $lines,
        callable $findVariant,
        string $providerPaymentId,
        PaymentStatus $paymentStatus,
        int $subtotal,
        int $discount,
        int $shipping,
        int $tax,
        int $total,
    ): Order {
        /** @var Order $order */
        $order = Order::query()->withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'order_number' => $orderNumber,
            'payment_method' => $paymentMethod,
            'status' => $orderStatus,
            'financial_status' => $financialStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'email' => $customer?->email,
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'placed_at' => $placedAt,
        ]);

        foreach ($lines as $lineData) {
            $variant = $findVariant($lineData['handle'], $lineData['option'] ?? null);
            $product = $variant?->product;

            OrderLine::query()->create([
                'order_id' => $order->id,
                'product_id' => $product?->id,
                'variant_id' => $variant?->id,
                'title_snapshot' => $product?->title ?? $lineData['handle'],
                'sku_snapshot' => $variant?->sku ?? 'N/A',
                'quantity' => $lineData['qty'],
                'unit_price_amount' => $lineData['price'],
                'total_amount' => $lineData['price'] * $lineData['qty'],
                'tax_lines_json' => [],
                'discount_allocations_json' => $lineData['discount_allocations'] ?? [],
            ]);
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $paymentMethod,
            'provider_payment_id' => $providerPaymentId,
            'status' => $paymentStatus,
            'amount' => $total,
            'currency' => 'EUR',
            'created_at' => $placedAt,
        ]);

        return $order;
    }

    private function createFullFulfillment(
        Order $order,
        ?string $trackingCompany,
        ?string $trackingNumber,
        \Illuminate\Support\Carbon $shippedAt,
        FulfillmentShipmentStatus $status,
    ): void {
        $order->loadMissing('lines');

        /** @var Fulfillment $fulfillment */
        $fulfillment = Fulfillment::query()->create([
            'order_id' => $order->id,
            'status' => $status,
            'tracking_company' => $trackingCompany,
            'tracking_number' => $trackingNumber,
            'shipped_at' => $shippedAt,
        ]);

        foreach ($order->lines as $line) {
            FulfillmentLine::query()->create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function customerAddress(?Customer $customer): array
    {
        if (! $customer) {
            return ['first_name' => 'Unknown', 'last_name' => 'Customer', 'address1' => 'Unknown', 'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115'];
        }

        $address = $customer->addresses()->where('is_default', true)->first();
        if ($address) {
            return $address->address_json;
        }

        $nameParts = explode(' ', $customer->name);

        return [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'address1' => 'Hauptstrasse 1',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'zip' => '10115',
        ];
    }

    private function seedAcmeElectronicsOrders(): void
    {
        $store = Store::where('handle', 'acme-electronics')->first();
        if (! $store) {
            return;
        }

        $techFan = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'techfan@example.com')->first();
        $gadgetLover = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'gadgetlover@example.com')->first();

        $findVariant = function (string $handle, ?string $optionMatch = null) use ($store): ?ProductVariant {
            $product = Product::query()->withoutGlobalScopes()
                ->where('store_id', $store->id)->where('handle', $handle)->first();
            if (! $product) {
                return null;
            }
            $query = ProductVariant::query()->where('product_id', $product->id);
            if ($optionMatch) {
                $query->whereHas('optionValues', fn ($q) => $q->where('value', $optionMatch));
            } else {
                $query->where('is_default', true);
            }

            return $query->first();
        };

        $defaultAddress = ['first_name' => 'Tech', 'last_name' => 'Fan', 'address1' => 'Techstrasse 1', 'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115'];

        // Order #5001
        $order5001 = $this->createOrder($store, $techFan, '#5001', PaymentMethod::CreditCard,
            OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled,
            now()->subDays(7), $defaultAddress,
            [
                ['handle' => 'pro-laptop-15', 'option' => '512GB', 'qty' => 1, 'price' => 119999],
                ['handle' => 'usb-c-cable-2m', 'option' => null, 'qty' => 1, 'price' => 1299],
            ],
            $findVariant, 'mock_test_order5001', PaymentStatus::Captured,
            121298, 0, 0, 0, 121298
        );
        $this->createFullFulfillment($order5001, 'DHL', 'DHL5001TRACK', now()->subDays(5), FulfillmentShipmentStatus::Delivered);

        // Order #5002
        $this->createOrder($store, $gadgetLover, '#5002', PaymentMethod::CreditCard,
            OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled,
            now()->subDays(3), ['first_name' => 'Gadget', 'last_name' => 'Lover', 'address1' => 'Gadgetweg 5', 'city' => 'Munich', 'country_code' => 'DE', 'zip' => '80331'],
            [['handle' => 'wireless-headphones', 'option' => 'Black', 'qty' => 1, 'price' => 14999]],
            $findVariant, 'mock_test_order5002', PaymentStatus::Captured,
            14999, 0, 0, 0, 14999
        );

        // Order #5003
        $this->createOrder($store, $techFan, '#5003', PaymentMethod::BankTransfer,
            OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled,
            now()->subDay(), $defaultAddress,
            [['handle' => 'monitor-stand', 'option' => null, 'qty' => 1, 'price' => 4999]],
            $findVariant, 'mock_test_order5003', PaymentStatus::Pending,
            4999, 0, 0, 0, 4999
        );
    }
}
