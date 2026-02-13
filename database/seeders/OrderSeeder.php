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
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    private int $fashionStoreId;

    private int $electronicsStoreId;

    /** @var array<string, int> */
    private array $customers = [];

    /** @var array<string, array{product_id: int, variant_id: int, sku: string}> */
    private array $variants = [];

    public function run(): void
    {
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();
            $this->fashionStoreId = $fashion->id;
            $this->electronicsStoreId = $electronics->id;

            $this->loadCustomers($fashion, $electronics);
            $this->loadVariants($fashion, $electronics);

            $this->seedFashionOrders();
            $this->seedElectronicsOrders();
        });
    }

    private function loadCustomers(Store $fashion, Store $electronics): void
    {
        $fashionCustomers = Customer::where('store_id', $fashion->id)->get();
        foreach ($fashionCustomers as $c) {
            $this->customers[$c->email] = $c->id;
        }
        $elecCustomers = Customer::where('store_id', $electronics->id)->get();
        foreach ($elecCustomers as $c) {
            $this->customers[$c->email] = $c->id;
        }
    }

    private function loadVariants(Store $fashion, Store $electronics): void
    {
        // Load all product variants with their SKUs for lookup
        $fashionProducts = Product::where('store_id', $fashion->id)->with('variants')->get();
        foreach ($fashionProducts as $product) {
            foreach ($product->variants as $variant) {
                if ($variant->sku) {
                    $this->variants[$variant->sku] = [
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                    ];
                }
            }
        }

        $elecProducts = Product::where('store_id', $electronics->id)->with('variants')->get();
        foreach ($elecProducts as $product) {
            foreach ($product->variants as $variant) {
                if ($variant->sku) {
                    $this->variants[$variant->sku] = [
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                    ];
                }
            }
        }
    }

    /**
     * Build address JSON for order addresses.
     *
     * @return array<string, string>
     */
    private function addressJson(
        string $firstName,
        string $lastName,
        string $address1,
        string $city,
        string $zip,
        string $countryCode = 'DE',
        string $company = '',
        string $address2 = '',
        string $phone = '',
    ): array {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => $company,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => $countryCode,
            'zip' => $zip,
            'phone' => $phone,
        ];
    }

    private function seedFashionOrders(): void
    {
        $johnAddr = $this->addressJson('John', 'Doe', 'Hauptstrasse 1', 'Berlin', '10115', 'DE', '', '', '+49 30 12345678');
        $janeAddr = $this->addressJson('Jane', 'Smith', 'Schillerstrasse 45', 'Munich', '80336');

        // Order #1001 - Awaiting fulfillment
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1001',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(2),
            'address' => $johnAddr,
            'lines' => [
                ['sku' => 'ACME-CTSH-S-WHT', 'title' => 'Classic Cotton T-Shirt - S / White', 'qty' => 2, 'unit_price' => 2499, 'total' => 4998],
            ],
            'subtotal' => 4998, 'discount' => 0, 'shipping' => 499, 'tax' => 798, 'total' => 5497,
            'payment' => ['id' => 'mock_test_order1001', 'status' => 'captured', 'amount' => 5497, 'method' => 'credit_card'],
        ]);

        // Order #1002 - Fully delivered
        $order1002 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1002',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(10),
            'address' => $johnAddr,
            'lines' => [
                ['sku' => 'ACME-HOOD-M', 'title' => 'Organic Hoodie - M', 'qty' => 1, 'unit_price' => 5999, 'total' => 5999],
                ['sku' => 'ACME-CTSH-L-BLK', 'title' => 'Classic Cotton T-Shirt - L / Black', 'qty' => 1, 'unit_price' => 2499, 'total' => 2499],
            ],
            'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
            'payment' => ['id' => 'mock_test_order1002', 'status' => 'captured', 'amount' => 8997, 'method' => 'credit_card'],
        ]);
        if ($order1002) {
            $this->createFulfillment($order1002, 'delivered', 'DHL', 'DHL1234567890', now()->subDays(8), true);
        }

        // Order #1003 - Partially fulfilled
        $order1003 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1003',
            'customer_email' => 'jane@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'partial',
            'placed_at' => now()->subDays(5),
            'address' => $janeAddr,
            'lines' => [
                ['sku' => 'ACME-JEANS-32-BLU', 'title' => 'Premium Slim Fit Jeans - 32 / Blue', 'qty' => 1, 'unit_price' => 7999, 'total' => 7999],
                ['sku' => 'ACME-BELT-LXL-BRN', 'title' => 'Leather Belt - L/XL / Brown', 'qty' => 1, 'unit_price' => 3499, 'total' => 3499],
            ],
            'subtotal' => 11498, 'discount' => 0, 'shipping' => 499, 'tax' => 1836, 'total' => 11997,
            'payment' => ['id' => 'mock_test_order1003', 'status' => 'captured', 'amount' => 11997, 'method' => 'credit_card'],
        ]);
        if ($order1003) {
            // Only the jeans line is fulfilled
            $jeansLine = OrderLine::where('order_id', $order1003->id)
                ->whereHas('variant', fn ($q) => $q->where('sku', 'ACME-JEANS-32-BLU'))
                ->first();
            if ($jeansLine) {
                $this->createPartialFulfillment($order1003, 'shipped', 'DHL', 'DHL9876543210', now()->subDays(3), [$jeansLine->id => 1]);
            }
        }

        // Order #1004 - Cancelled with full refund
        $order1004 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1004',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'credit_card',
            'status' => 'cancelled',
            'financial_status' => 'refunded',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(15),
            'address' => $johnAddr,
            'lines' => [
                ['sku' => 'ACME-CTSH-M-NVY', 'title' => 'Classic Cotton T-Shirt - M / Navy', 'qty' => 1, 'unit_price' => 2499, 'total' => 2499],
            ],
            'subtotal' => 2499, 'discount' => 0, 'shipping' => 499, 'tax' => 399, 'total' => 2998,
            'payment' => ['id' => 'mock_test_order1004', 'status' => 'refunded', 'amount' => 2998, 'method' => 'credit_card'],
        ]);
        if ($order1004) {
            $payment = Payment::where('order_id', $order1004->id)->first();
            if ($payment) {
                Refund::firstOrCreate(
                    ['order_id' => $order1004->id, 'provider_refund_id' => 'mock_re_test_order1004'],
                    [
                        'payment_id' => $payment->id,
                        'amount' => 2998,
                        'reason' => 'Customer requested cancellation',
                        'status' => 'processed',
                        'created_at' => now()->subDays(14),
                    ],
                );
            }
        }

        // Order #1005 - Bank transfer awaiting payment
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1005',
            'customer_email' => 'jane@example.com',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subHours(2),
            'address' => $janeAddr,
            'lines' => [
                ['sku' => 'ACME-BELT-SM-BLK', 'title' => 'Leather Belt - S/M / Black', 'qty' => 1, 'unit_price' => 3499, 'total' => 3499],
            ],
            'subtotal' => 3499, 'discount' => 0, 'shipping' => 499, 'tax' => 559, 'total' => 3998,
            'payment' => ['id' => 'mock_test_order1005', 'status' => 'pending', 'amount' => 3998, 'method' => 'bank_transfer'],
        ]);

        // Order #1006 - Standard paid order
        $michaelAddr = $this->addressJson('Michael', 'Brown', 'Bahnhofstrasse 12', 'Hamburg', '20095');
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1006',
            'customer_email' => 'michael@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay(),
            'address' => $michaelAddr,
            'lines' => [
                ['sku' => 'ACME-SNKR-EU42-BLK', 'title' => 'Running Sneakers - EU 42 / Black', 'qty' => 1, 'unit_price' => 11999, 'total' => 11999],
            ],
            'subtotal' => 11999, 'discount' => 0, 'shipping' => 499, 'tax' => 1916, 'total' => 12498,
            'payment' => ['id' => 'mock_test_order1006', 'status' => 'captured', 'amount' => 12498, 'method' => 'credit_card'],
        ]);

        // Order #1007 - Multi-item delivered (PayPal)
        $sarahAddr = $this->addressJson('Sarah', 'Wilson', 'Koenigstrasse 5', 'Stuttgart', '70173');
        $order1007 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1007',
            'customer_email' => 'sarah@example.com',
            'payment_method' => 'paypal',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(20),
            'address' => $sarahAddr,
            'lines' => [
                ['sku' => 'ACME-VNCK-M-BEI', 'title' => 'V-Neck Linen Tee - M / Beige', 'qty' => 2, 'unit_price' => 3499, 'total' => 6998],
                ['sku' => 'ACME-SCRF-GRY', 'title' => 'Wool Scarf - Grey', 'qty' => 1, 'unit_price' => 2999, 'total' => 2999],
            ],
            'subtotal' => 9997, 'discount' => 0, 'shipping' => 499, 'tax' => 1596, 'total' => 10496,
            'payment' => ['id' => 'mock_test_order1007', 'status' => 'captured', 'amount' => 10496, 'method' => 'paypal'],
        ]);
        if ($order1007) {
            $this->createFulfillment($order1007, 'delivered', 'DHL', 'DHL1112223334', now()->subDays(18), true);
        }

        // Order #1008 - Partial refund
        $davidAddr = $this->addressJson('David', 'Lee', 'Marktplatz 8', 'Frankfurt', '60311');
        $order1008 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1008',
            'customer_email' => 'david@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'partially_refunded',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(12),
            'address' => $davidAddr,
            'lines' => [
                ['sku' => 'ACME-CARG-32-KHK', 'title' => 'Cargo Pants - 32 / Khaki', 'qty' => 1, 'unit_price' => 5499, 'total' => 5499],
                ['sku' => 'ACME-GRPH-L', 'title' => 'Graphic Print Tee - L', 'qty' => 1, 'unit_price' => 2999, 'total' => 2999],
            ],
            'subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997,
            'payment' => ['id' => 'mock_test_order1008', 'status' => 'captured', 'amount' => 8997, 'method' => 'credit_card'],
        ]);
        if ($order1008) {
            $this->createFulfillment($order1008, 'delivered', 'UPS', 'UPS5556667778', now()->subDays(10), true);
            $payment = Payment::where('order_id', $order1008->id)->first();
            if ($payment) {
                Refund::firstOrCreate(
                    ['order_id' => $order1008->id, 'provider_refund_id' => 'mock_re_test_order1008'],
                    [
                        'payment_id' => $payment->id,
                        'amount' => 2999,
                        'reason' => 'Item returned',
                        'status' => 'processed',
                        'created_at' => now()->subDays(8),
                    ],
                );
            }
        }

        // Order #1009 - Accessories order
        $emmaAddr = $this->addressJson('Emma', 'Garcia', 'Schlossallee 22', 'Dresden', '01067');
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1009',
            'customer_email' => 'emma@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(3),
            'address' => $emmaAddr,
            'lines' => [
                ['sku' => 'ACME-TOTE-NAT', 'title' => 'Canvas Tote Bag - Natural', 'qty' => 1, 'unit_price' => 1999, 'total' => 1999],
                ['sku' => 'ACME-BHAT-SM-BLK', 'title' => 'Bucket Hat - S/M / Black', 'qty' => 1, 'unit_price' => 2499, 'total' => 2499],
            ],
            'subtotal' => 4498, 'discount' => 0, 'shipping' => 499, 'tax' => 718, 'total' => 4997,
            'payment' => ['id' => 'mock_test_order1009', 'status' => 'captured', 'amount' => 4997, 'method' => 'credit_card'],
        ]);

        // Order #1010 - High-value order (PayPal)
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1010',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'paypal',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay(),
            'address' => $johnAddr,
            'lines' => [
                ['sku' => 'ACME-CASH-M-CML', 'title' => 'Cashmere Overcoat - M / Camel', 'qty' => 1, 'unit_price' => 49999, 'total' => 49999],
            ],
            'subtotal' => 49999, 'discount' => 0, 'shipping' => 499, 'tax' => 7983, 'total' => 50498,
            'payment' => ['id' => 'mock_test_order1010', 'status' => 'captured', 'amount' => 50498, 'method' => 'paypal'],
        ]);

        // Order #1011 - Single item delivered
        $jamesAddr = $this->addressJson('James', 'Taylor', 'Lindenstrasse 15', 'Cologne', '50667');
        $order1011 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1011',
            'customer_email' => 'james@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(25),
            'address' => $jamesAddr,
            'lines' => [
                ['sku' => 'ACME-POLO-XL', 'title' => 'Striped Polo Shirt - XL', 'qty' => 1, 'unit_price' => 2799, 'total' => 2799],
            ],
            'subtotal' => 2799, 'discount' => 0, 'shipping' => 499, 'tax' => 447, 'total' => 3298,
            'payment' => ['id' => 'mock_test_order1011', 'status' => 'captured', 'amount' => 3298, 'method' => 'credit_card'],
        ]);
        if ($order1011) {
            $this->createFulfillment($order1011, 'delivered', 'FedEx', 'FX9998887776', now()->subDays(23), true);
        }

        // Order #1012 - Multi-quantity order
        $lisaAddr = $this->addressJson('Lisa', 'Anderson', 'Gartenweg 3', 'Dusseldorf', '40213');
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1012',
            'customer_email' => 'lisa@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(4),
            'address' => $lisaAddr,
            'lines' => [
                ['sku' => 'ACME-CHNO-34-NVY', 'title' => 'Chino Shorts - 34 / Navy', 'qty' => 2, 'unit_price' => 3999, 'total' => 7998],
            ],
            'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
            'payment' => ['id' => 'mock_test_order1012', 'status' => 'captured', 'amount' => 8497, 'method' => 'credit_card'],
        ]);

        // Order #1013 - Multi-item order
        $robertAddr = $this->addressJson('Robert', 'Martinez', 'Bergstrasse 7', 'Leipzig', '04109');
        $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1013',
            'customer_email' => 'robert@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay(),
            'address' => $robertAddr,
            'lines' => [
                ['sku' => 'ACME-WLEG-M', 'title' => 'Wide Leg Trousers - M', 'qty' => 1, 'unit_price' => 4999, 'total' => 4999],
                ['sku' => 'ACME-SCRF-BRG', 'title' => 'Wool Scarf - Burgundy', 'qty' => 1, 'unit_price' => 2999, 'total' => 2999],
            ],
            'subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497,
            'payment' => ['id' => 'mock_test_order1013', 'status' => 'captured', 'amount' => 8497, 'method' => 'credit_card'],
        ]);

        // Order #1014 - Digital product order
        $annaAddr = $this->addressJson('Anna', 'Thomas', 'Kirchgasse 19', 'Nuremberg', '90402');
        $order1014 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1014',
            'customer_email' => 'anna@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(14),
            'address' => $annaAddr,
            'lines' => [
                ['sku' => 'ACME-GIFT-50', 'title' => 'Gift Card - 50 EUR', 'qty' => 1, 'unit_price' => 5000, 'total' => 5000],
            ],
            'subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 5000,
            'payment' => ['id' => 'mock_test_order1014', 'status' => 'captured', 'amount' => 5000, 'method' => 'credit_card'],
        ]);
        if ($order1014) {
            // Auto-fulfilled digital product
            $fulfillment = Fulfillment::firstOrCreate(
                ['order_id' => $order1014->id, 'tracking_number' => null],
                [
                    'status' => 'delivered',
                    'tracking_company' => null,
                    'tracking_url' => null,
                    'shipped_at' => $order1014->placed_at,
                    'created_at' => $order1014->placed_at,
                ],
            );

            $orderLines = OrderLine::where('order_id', $order1014->id)->get();
            foreach ($orderLines as $line) {
                FulfillmentLine::firstOrCreate(
                    ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line->id],
                    ['quantity' => $line->quantity],
                );
            }
        }

        // Order #1015 - Order with discount (Bank Transfer, confirmed)
        $welcome10 = Discount::where('store_id', $this->fashionStoreId)->where('code', 'WELCOME10')->first();
        $order1015 = $this->createOrder([
            'store_id' => $this->fashionStoreId,
            'order_number' => '#1015',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now(),
            'address' => $johnAddr,
            'lines' => [
                [
                    'sku' => 'ACME-CTSH-M-WHT',
                    'title' => 'Classic Cotton T-Shirt - M / White',
                    'qty' => 1,
                    'unit_price' => 2499,
                    'total' => 2499,
                    'discount_allocations' => $welcome10 ? [['discount_id' => $welcome10->id, 'code' => 'WELCOME10', 'amount' => 250]] : [],
                ],
                [
                    'sku' => 'ACME-GRPH-M',
                    'title' => 'Graphic Print Tee - M',
                    'qty' => 1,
                    'unit_price' => 2999,
                    'total' => 2999,
                    'discount_allocations' => $welcome10 ? [['discount_id' => $welcome10->id, 'code' => 'WELCOME10', 'amount' => 300]] : [],
                ],
            ],
            'subtotal' => 5498, 'discount' => 550, 'shipping' => 499, 'tax' => 790, 'total' => 5447,
            'payment' => ['id' => 'mock_test_order1015', 'status' => 'captured', 'amount' => 5447, 'method' => 'bank_transfer'],
        ]);
    }

    private function seedElectronicsOrders(): void
    {
        $techAddr = $this->addressJson('Tech', 'Fan', 'Techstrasse 1', 'Berlin', '10115');
        $gadgetAddr = $this->addressJson('Gadget', 'Lover', 'Techstrasse 2', 'Berlin', '10115');

        // Order #5001 - Fulfilled
        $order5001 = $this->createOrder([
            'store_id' => $this->electronicsStoreId,
            'order_number' => '#5001',
            'customer_email' => 'techfan@example.com',
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'placed_at' => now()->subDays(7),
            'address' => $techAddr,
            'lines' => [
                ['sku' => 'ELEC-LAP-512', 'title' => 'Pro Laptop 15 - 512GB', 'qty' => 1, 'unit_price' => 119999, 'total' => 119999],
                ['sku' => 'ELEC-USBC-2M', 'title' => 'USB-C Cable 2m', 'qty' => 1, 'unit_price' => 1299, 'total' => 1299],
            ],
            'subtotal' => 121298, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 121298,
            'payment' => ['id' => 'mock_test_order5001', 'status' => 'captured', 'amount' => 121298, 'method' => 'credit_card'],
        ]);
        if ($order5001) {
            $this->createFulfillment($order5001, 'delivered', 'DHL', 'DHL5001000001', now()->subDays(5), true);
        }

        // Order #5002 - Unfulfilled
        $this->createOrder([
            'store_id' => $this->electronicsStoreId,
            'order_number' => '#5002',
            'customer_email' => 'gadgetlover@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDays(2),
            'address' => $gadgetAddr,
            'lines' => [
                ['sku' => 'ELEC-HEAD-BLK', 'title' => 'Wireless Headphones - Black', 'qty' => 1, 'unit_price' => 14999, 'total' => 14999],
            ],
            'subtotal' => 14999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 14999,
            'payment' => ['id' => 'mock_test_order5002', 'status' => 'captured', 'amount' => 14999, 'method' => 'credit_card'],
        ]);

        // Order #5003 - Bank transfer pending
        $this->createOrder([
            'store_id' => $this->electronicsStoreId,
            'order_number' => '#5003',
            'customer_email' => 'techfan@example.com',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now()->subDay(),
            'address' => $techAddr,
            'lines' => [
                ['sku' => 'ELEC-MSTAND', 'title' => 'Monitor Stand', 'qty' => 1, 'unit_price' => 4999, 'total' => 4999],
            ],
            'subtotal' => 4999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 4999,
            'payment' => ['id' => 'mock_test_order5003', 'status' => 'pending', 'amount' => 4999, 'method' => 'bank_transfer'],
        ]);
    }

    private function createOrder(array $data): ?Order
    {
        $customerEmail = $data['customer_email'];
        $customerId = $this->customers[$customerEmail] ?? null;

        $order = Order::firstOrCreate(
            ['store_id' => $data['store_id'], 'order_number' => $data['order_number']],
            [
                'customer_id' => $customerId,
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
                'email' => $customerEmail,
                'billing_address_json' => $data['address'],
                'shipping_address_json' => $data['address'],
                'placed_at' => $data['placed_at'],
            ],
        );

        // Create order lines
        foreach ($data['lines'] as $line) {
            $variantData = $this->variants[$line['sku']] ?? null;

            OrderLine::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'sku_snapshot' => $line['sku'],
                ],
                [
                    'product_id' => $variantData['product_id'] ?? null,
                    'variant_id' => $variantData['variant_id'] ?? null,
                    'title_snapshot' => $line['title'],
                    'quantity' => $line['qty'],
                    'unit_price_amount' => $line['unit_price'],
                    'total_amount' => $line['total'],
                    'tax_lines_json' => [],
                    'discount_allocations_json' => $line['discount_allocations'] ?? [],
                ],
            );
        }

        // Create payment
        $paymentData = $data['payment'];
        Payment::firstOrCreate(
            ['order_id' => $order->id, 'provider_payment_id' => $paymentData['id']],
            [
                'provider' => 'mock',
                'method' => $paymentData['method'],
                'status' => $paymentData['status'],
                'amount' => $paymentData['amount'],
                'currency' => 'EUR',
                'raw_json_encrypted' => null,
                'created_at' => $data['placed_at'],
            ],
        );

        return $order;
    }

    private function createFulfillment(Order $order, string $status, string $company, string $trackingNumber, $shippedAt, bool $allLines = true): void
    {
        $fulfillment = Fulfillment::firstOrCreate(
            ['order_id' => $order->id, 'tracking_number' => $trackingNumber],
            [
                'status' => $status,
                'tracking_company' => $company,
                'tracking_url' => 'https://tracking.example.com/'.$trackingNumber,
                'shipped_at' => $shippedAt,
                'created_at' => $shippedAt,
            ],
        );

        if ($allLines) {
            $orderLines = OrderLine::where('order_id', $order->id)->get();
            foreach ($orderLines as $line) {
                FulfillmentLine::firstOrCreate(
                    ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line->id],
                    ['quantity' => $line->quantity],
                );
            }
        }
    }

    private function createPartialFulfillment(Order $order, string $status, string $company, string $trackingNumber, $shippedAt, array $lineQtys): void
    {
        $fulfillment = Fulfillment::firstOrCreate(
            ['order_id' => $order->id, 'tracking_number' => $trackingNumber],
            [
                'status' => $status,
                'tracking_company' => $company,
                'tracking_url' => 'https://tracking.example.com/'.$trackingNumber,
                'shipped_at' => $shippedAt,
                'created_at' => $shippedAt,
            ],
        );

        foreach ($lineQtys as $lineId => $qty) {
            FulfillmentLine::firstOrCreate(
                ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $lineId],
                ['quantity' => $qty],
            );
        }
    }
}
