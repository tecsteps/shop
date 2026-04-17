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
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedFashionOrders();
        });

        DB::transaction(function () {
            $this->seedElectronicsOrders();
        });
    }

    private function seedFashionOrders(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        app()->instance('current_store', $store);

        $customers = Customer::where('store_id', $store->id)->get()->keyBy('email');
        $products = Product::where('store_id', $store->id)->with(['variants.optionValues'])->get()->keyBy('handle');

        $johnAddress = [
            'first_name' => 'John', 'last_name' => 'Doe', 'company' => '',
            'address1' => 'Hauptstrasse 1', 'address2' => '', 'city' => 'Berlin',
            'province' => '', 'province_code' => '', 'country' => 'Germany',
            'country_code' => 'DE', 'zip' => '10115', 'phone' => '+49 30 12345678',
        ];

        $janeAddress = [
            'first_name' => 'Jane', 'last_name' => 'Smith', 'company' => '',
            'address1' => 'Schillerstrasse 45', 'address2' => '', 'city' => 'Munich',
            'province' => 'Bavaria', 'province_code' => 'BY', 'country' => 'Germany',
            'country_code' => 'DE', 'zip' => '80336', 'phone' => '',
        ];

        $defaultAddress = function (string $name) {
            [$first, $last] = explode(' ', $name, 2);

            return [
                'first_name' => $first, 'last_name' => $last, 'company' => '',
                'address1' => 'Musterstrasse '.rand(1, 100), 'address2' => '',
                'city' => 'Berlin', 'province' => '', 'province_code' => '',
                'country' => 'Germany', 'country_code' => 'DE',
                'zip' => '10'.rand(100, 999), 'phone' => '',
            ];
        };

        // Order #1001 - Awaiting fulfillment
        $this->createOrder($store, $customers['customer@acme.test'], '#1001', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(2),
            'address' => $johnAddress,
            'lines' => [
                ['product' => $products['classic-cotton-t-shirt'], 'variant_match' => ['S', 'White'], 'qty' => 2, 'unit_price' => 2499, 'total' => 4998],
            ],
            'subtotal' => 4998, 'discount' => 0, 'shipping' => 499, 'tax' => 798, 'total' => 5497,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1001', 'status' => PaymentStatus::Captured, 'amount' => 5497],
        ]);

        // Order #1002 - Fully delivered
        $order1002 = $this->createOrder($store, $customers['customer@acme.test'], '#1002', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'placed_at' => now()->subDays(10),
            'address' => $johnAddress,
            'lines' => [
                ['product' => $products['organic-hoodie'], 'variant_match' => ['M'], 'qty' => 1, 'unit_price' => 5999, 'total' => 5999],
                ['product' => $products['classic-cotton-t-shirt'], 'variant_match' => ['L', 'Black'], 'qty' => 1, 'unit_price' => 2499, 'total' => 2499],
            ],
            'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1002', 'status' => PaymentStatus::Captured, 'amount' => 8997],
        ]);
        $this->createFulfillment($order1002, FulfillmentShipmentStatus::Delivered, 'DHL', 'DHL1234567890', now()->subDays(8));

        // Order #1003 - Partially fulfilled
        $order1003 = $this->createOrder($store, $customers['jane@example.com'], '#1003', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Partial,
            'placed_at' => now()->subDays(5),
            'address' => $janeAddress,
            'lines' => [
                ['product' => $products['premium-slim-fit-jeans'], 'variant_match' => ['32', 'Blue'], 'qty' => 1, 'unit_price' => 7999, 'total' => 7999],
                ['product' => $products['leather-belt'], 'variant_match' => ['L/XL', 'Brown'], 'qty' => 1, 'unit_price' => 3499, 'total' => 3499],
            ],
            'subtotal' => 11498, 'discount' => 0, 'shipping' => 499, 'tax' => 1836, 'total' => 11997,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1003', 'status' => PaymentStatus::Captured, 'amount' => 11997],
        ]);
        $this->createPartialFulfillment($order1003, FulfillmentShipmentStatus::Shipped, 'DHL', 'DHL9876543210', now()->subDays(3), [0]);

        // Order #1004 - Cancelled with full refund
        $order1004 = $this->createOrder($store, $customers['customer@acme.test'], '#1004', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Cancelled,
            'financial_status' => FinancialStatus::Refunded,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(15),
            'address' => $johnAddress,
            'lines' => [
                ['product' => $products['classic-cotton-t-shirt'], 'variant_match' => ['M', 'Navy'], 'qty' => 1, 'unit_price' => 2499, 'total' => 2499],
            ],
            'subtotal' => 2499, 'discount' => 0, 'shipping' => 499, 'tax' => 399, 'total' => 2998,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1004', 'status' => PaymentStatus::Refunded, 'amount' => 2998],
        ]);
        $this->createRefund($order1004, 2998, 'Customer requested cancellation', 'mock_re_test_order1004');

        // Order #1005 - Bank transfer awaiting payment
        $this->createOrder($store, $customers['jane@example.com'], '#1005', [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subHours(2),
            'address' => $janeAddress,
            'lines' => [
                ['product' => $products['leather-belt'], 'variant_match' => ['S/M', 'Black'], 'qty' => 1, 'unit_price' => 3499, 'total' => 3499],
            ],
            'subtotal' => 3499, 'discount' => 0, 'shipping' => 499, 'tax' => 559, 'total' => 3998,
            'payment' => ['method' => PaymentMethod::BankTransfer, 'provider_id' => 'mock_test_order1005', 'status' => PaymentStatus::Pending, 'amount' => 3998],
        ]);

        // Order #1006 - Standard paid order
        $this->createOrder($store, $customers['michael@example.com'], '#1006', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(1),
            'address' => $defaultAddress('Michael Brown'),
            'lines' => [
                ['product' => $products['running-sneakers'], 'variant_match' => ['EU 42', 'Black'], 'qty' => 1, 'unit_price' => 11999, 'total' => 11999],
            ],
            'subtotal' => 11999, 'discount' => 0, 'shipping' => 499, 'tax' => 1916, 'total' => 12498,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1006', 'status' => PaymentStatus::Captured, 'amount' => 12498],
        ]);

        // Order #1007 - Multi-item delivered (PayPal)
        $order1007 = $this->createOrder($store, $customers['sarah@example.com'], '#1007', [
            'payment_method' => PaymentMethod::Paypal,
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'placed_at' => now()->subDays(20),
            'address' => $defaultAddress('Sarah Wilson'),
            'lines' => [
                ['product' => $products['v-neck-linen-tee'], 'variant_match' => ['M', 'Beige'], 'qty' => 2, 'unit_price' => 3499, 'total' => 6998],
                ['product' => $products['wool-scarf'], 'variant_match' => ['Grey'], 'qty' => 1, 'unit_price' => 2999, 'total' => 2999],
            ],
            'subtotal' => 9997, 'discount' => 0, 'shipping' => 499, 'tax' => 1596, 'total' => 10496,
            'payment' => ['method' => PaymentMethod::Paypal, 'provider_id' => 'mock_test_order1007', 'status' => PaymentStatus::Captured, 'amount' => 10496],
        ]);
        $this->createFulfillment($order1007, FulfillmentShipmentStatus::Delivered, 'DHL', 'DHL1112223334', now()->subDays(18));

        // Order #1008 - Partial refund
        $order1008 = $this->createOrder($store, $customers['david@example.com'], '#1008', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::PartiallyRefunded,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'placed_at' => now()->subDays(12),
            'address' => $defaultAddress('David Lee'),
            'lines' => [
                ['product' => $products['cargo-pants'], 'variant_match' => ['32', 'Khaki'], 'qty' => 1, 'unit_price' => 5499, 'total' => 5499],
                ['product' => $products['graphic-print-tee'], 'variant_match' => ['L'], 'qty' => 1, 'unit_price' => 2999, 'total' => 2999],
            ],
            'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1008', 'status' => PaymentStatus::Captured, 'amount' => 8997],
        ]);
        $this->createFulfillment($order1008, FulfillmentShipmentStatus::Delivered, 'UPS', 'UPS5556667778', now()->subDays(10));
        $this->createRefund($order1008, 2999, 'Item returned', 'mock_re_test_order1008');

        // Order #1009 - Accessories order
        $this->createOrder($store, $customers['emma@example.com'], '#1009', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(3),
            'address' => $defaultAddress('Emma Garcia'),
            'lines' => [
                ['product' => $products['canvas-tote-bag'], 'variant_match' => ['Natural'], 'qty' => 1, 'unit_price' => 1999, 'total' => 1999],
                ['product' => $products['bucket-hat'], 'variant_match' => ['S/M', 'Black'], 'qty' => 1, 'unit_price' => 2499, 'total' => 2499],
            ],
            'subtotal' => 4498, 'discount' => 0, 'shipping' => 499, 'tax' => 718, 'total' => 4997,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1009', 'status' => PaymentStatus::Captured, 'amount' => 4997],
        ]);

        // Order #1010 - High-value order (PayPal)
        $this->createOrder($store, $customers['customer@acme.test'], '#1010', [
            'payment_method' => PaymentMethod::Paypal,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(1),
            'address' => $johnAddress,
            'lines' => [
                ['product' => $products['cashmere-overcoat'], 'variant_match' => ['M', 'Camel'], 'qty' => 1, 'unit_price' => 49999, 'total' => 49999],
            ],
            'subtotal' => 49999, 'discount' => 0, 'shipping' => 499, 'tax' => 7983, 'total' => 50498,
            'payment' => ['method' => PaymentMethod::Paypal, 'provider_id' => 'mock_test_order1010', 'status' => PaymentStatus::Captured, 'amount' => 50498],
        ]);

        // Order #1011 - Single item delivered
        $order1011 = $this->createOrder($store, $customers['james@example.com'], '#1011', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'placed_at' => now()->subDays(25),
            'address' => $defaultAddress('James Taylor'),
            'lines' => [
                ['product' => $products['striped-polo-shirt'], 'variant_match' => ['XL'], 'qty' => 1, 'unit_price' => 2799, 'total' => 2799],
            ],
            'subtotal' => 2799, 'discount' => 0, 'shipping' => 499, 'tax' => 447, 'total' => 3298,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1011', 'status' => PaymentStatus::Captured, 'amount' => 3298],
        ]);
        $this->createFulfillment($order1011, FulfillmentShipmentStatus::Delivered, 'FedEx', 'FX9998887776', now()->subDays(23));

        // Order #1012 - Multi-quantity order
        $this->createOrder($store, $customers['lisa@example.com'], '#1012', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(4),
            'address' => $defaultAddress('Lisa Anderson'),
            'lines' => [
                ['product' => $products['chino-shorts'], 'variant_match' => ['34', 'Navy'], 'qty' => 2, 'unit_price' => 3999, 'total' => 7998],
            ],
            'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1012', 'status' => PaymentStatus::Captured, 'amount' => 8497],
        ]);

        // Order #1013 - Multi-item order
        $this->createOrder($store, $customers['robert@example.com'], '#1013', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(1),
            'address' => $defaultAddress('Robert Martinez'),
            'lines' => [
                ['product' => $products['wide-leg-trousers'], 'variant_match' => ['M'], 'qty' => 1, 'unit_price' => 4999, 'total' => 4999],
                ['product' => $products['wool-scarf'], 'variant_match' => ['Burgundy'], 'qty' => 1, 'unit_price' => 2999, 'total' => 2999],
            ],
            'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1013', 'status' => PaymentStatus::Captured, 'amount' => 8497],
        ]);

        // Order #1014 - Digital product order
        $order1014 = $this->createOrder($store, $customers['anna@example.com'], '#1014', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'placed_at' => now()->subDays(14),
            'address' => $defaultAddress('Anna Thomas'),
            'lines' => [
                ['product' => $products['gift-card'], 'variant_match' => ['50 EUR'], 'qty' => 1, 'unit_price' => 5000, 'total' => 5000],
            ],
            'subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 5000,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order1014', 'status' => PaymentStatus::Captured, 'amount' => 5000],
        ]);
        // Auto-fulfilled digital product
        $placedAt = now()->subDays(14);
        $fulfillment = Fulfillment::create([
            'order_id' => $order1014->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'tracking_company' => null,
            'tracking_number' => null,
            'shipped_at' => $placedAt,
            'created_at' => $placedAt,
        ]);
        foreach ($order1014->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        // Order #1015 - Order with discount (Bank Transfer, confirmed)
        $welcome10 = Discount::where('store_id', $store->id)->where('code', 'WELCOME10')->first();
        $order1015 = $this->createOrder($store, $customers['customer@acme.test'], '#1015', [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now(),
            'address' => $johnAddress,
            'lines' => [
                [
                    'product' => $products['classic-cotton-t-shirt'], 'variant_match' => ['M', 'White'],
                    'qty' => 1, 'unit_price' => 2499, 'total' => 2499,
                    'discount_allocations' => $welcome10 ? [['discount_id' => $welcome10->id, 'amount' => 250]] : [],
                ],
                [
                    'product' => $products['graphic-print-tee'], 'variant_match' => ['M'],
                    'qty' => 1, 'unit_price' => 2999, 'total' => 2999,
                    'discount_allocations' => $welcome10 ? [['discount_id' => $welcome10->id, 'amount' => 300]] : [],
                ],
            ],
            'subtotal' => 5498, 'discount' => 550, 'shipping' => 499, 'tax' => 790, 'total' => 5447,
            'payment' => ['method' => PaymentMethod::BankTransfer, 'provider_id' => 'mock_test_order1015', 'status' => PaymentStatus::Captured, 'amount' => 5447],
        ]);
    }

    private function seedElectronicsOrders(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();
        app()->instance('current_store', $store);

        $customers = Customer::where('store_id', $store->id)->get()->keyBy('email');
        $products = Product::where('store_id', $store->id)->with(['variants.optionValues'])->get()->keyBy('handle');

        $techFanAddress = [
            'first_name' => 'Tech', 'last_name' => 'Fan', 'company' => '',
            'address1' => 'Techstrasse 1', 'address2' => '', 'city' => 'Berlin',
            'province' => '', 'province_code' => '', 'country' => 'Germany',
            'country_code' => 'DE', 'zip' => '10115', 'phone' => '',
        ];

        $gadgetAddress = [
            'first_name' => 'Gadget', 'last_name' => 'Lover', 'company' => '',
            'address1' => 'Gadgetweg 5', 'address2' => '', 'city' => 'Hamburg',
            'province' => '', 'province_code' => '', 'country' => 'Germany',
            'country_code' => 'DE', 'zip' => '20095', 'phone' => '',
        ];

        // Order #5001 - Pro Laptop + USB-C Cable
        $order5001 = $this->createOrder($store, $customers['techfan@example.com'], '#5001', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'placed_at' => now()->subDays(7),
            'address' => $techFanAddress,
            'lines' => [
                ['product' => $products['pro-laptop-15'], 'variant_match' => ['512GB'], 'qty' => 1, 'unit_price' => 119999, 'total' => 119999],
                ['product' => $products['usb-c-cable-2m'], 'variant_match' => [], 'qty' => 1, 'unit_price' => 1299, 'total' => 1299],
            ],
            'subtotal' => 121298, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 121298,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order5001', 'status' => PaymentStatus::Captured, 'amount' => 121298],
        ]);
        $this->createFulfillment($order5001, FulfillmentShipmentStatus::Delivered, 'DHL', 'DHL5001000001', now()->subDays(5));

        // Order #5002 - Wireless Headphones
        $this->createOrder($store, $customers['gadgetlover@example.com'], '#5002', [
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(2),
            'address' => $gadgetAddress,
            'lines' => [
                ['product' => $products['wireless-headphones'], 'variant_match' => ['Black'], 'qty' => 1, 'unit_price' => 14999, 'total' => 14999],
            ],
            'subtotal' => 14999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 14999,
            'payment' => ['method' => PaymentMethod::CreditCard, 'provider_id' => 'mock_test_order5002', 'status' => PaymentStatus::Captured, 'amount' => 14999],
        ]);

        // Order #5003 - Monitor Stand, bank transfer pending
        $this->createOrder($store, $customers['techfan@example.com'], '#5003', [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'placed_at' => now()->subDays(1),
            'address' => $techFanAddress,
            'lines' => [
                ['product' => $products['monitor-stand'], 'variant_match' => [], 'qty' => 1, 'unit_price' => 4999, 'total' => 4999],
            ],
            'subtotal' => 4999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 4999,
            'payment' => ['method' => PaymentMethod::BankTransfer, 'provider_id' => 'mock_test_order5003', 'status' => PaymentStatus::Pending, 'amount' => 4999],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createOrder(Store $store, Customer $customer, string $orderNumber, array $data): Order
    {
        $order = Order::firstOrCreate(
            ['store_id' => $store->id, 'order_number' => $orderNumber],
            [
                'customer_id' => $customer->id,
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
                'billing_address_json' => $data['address'],
                'shipping_address_json' => $data['address'],
                'placed_at' => $data['placed_at'],
            ]
        );

        if ($order->lines()->exists()) {
            return $order;
        }

        foreach ($data['lines'] as $lineData) {
            $product = $lineData['product'];
            $variant = $this->findVariant($product, $lineData['variant_match']);

            OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $lineData['qty'],
                'unit_price_amount' => $lineData['unit_price'],
                'total_amount' => $lineData['total'],
                'tax_lines_json' => [],
                'discount_allocations_json' => $lineData['discount_allocations'] ?? [],
            ]);
        }

        // Create payment
        $paymentData = $data['payment'];
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $paymentData['method'],
            'provider_payment_id' => $paymentData['provider_id'],
            'status' => $paymentData['status'],
            'amount' => $paymentData['amount'],
            'currency' => 'EUR',
            'created_at' => $data['placed_at'],
        ]);

        return $order;
    }

    private function findVariant(Product $product, array $matchValues): \App\Models\ProductVariant
    {
        if (empty($matchValues)) {
            return $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        }

        foreach ($product->variants as $variant) {
            $variantValues = $variant->optionValues->pluck('value')->toArray();
            if (count(array_diff($matchValues, $variantValues)) === 0) {
                return $variant;
            }
        }

        return $product->variants->first();
    }

    private function createFulfillment(Order $order, FulfillmentShipmentStatus $status, string $company, string $tracking, \Carbon\CarbonInterface $shippedAt): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => $status,
            'tracking_company' => $company,
            'tracking_number' => $tracking,
            'shipped_at' => $shippedAt,
            'created_at' => $shippedAt,
        ]);

        $order->load('lines');
        foreach ($order->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $lineIndices
     */
    private function createPartialFulfillment(Order $order, FulfillmentShipmentStatus $status, string $company, string $tracking, \Carbon\CarbonInterface $shippedAt, array $lineIndices): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => $status,
            'tracking_company' => $company,
            'tracking_number' => $tracking,
            'shipped_at' => $shippedAt,
            'created_at' => $shippedAt,
        ]);

        $order->load('lines');
        $lines = $order->lines->values();
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

    private function createRefund(Order $order, int $amount, string $reason, string $providerRefundId): void
    {
        $payment = $order->payments()->first();

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment?->id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => RefundStatus::Processed,
            'provider_refund_id' => $providerRefundId,
            'created_at' => now(),
        ]);
    }
}
