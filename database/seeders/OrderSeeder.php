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

class OrderSeeder extends Seeder
{
    private Store $fashionStore;

    private Store $electronicsStore;

    /** @var array<string, int> */
    private array $customerIds = [];

    public function run(): void
    {
        $this->fashionStore = Store::where('handle', 'acme-fashion')->firstOrFail();
        $this->electronicsStore = Store::where('handle', 'acme-electronics')->firstOrFail();

        $this->loadCustomers();
        $this->seedFashionOrders();
        $this->seedElectronicsOrders();
    }

    private function loadCustomers(): void
    {
        // Fashion customers
        $fashionCustomers = Customer::withoutGlobalScopes()
            ->where('store_id', $this->fashionStore->id)
            ->get();

        foreach ($fashionCustomers as $customer) {
            $this->customerIds[$customer->email] = $customer->id;
        }

        // Electronics customers
        $electronicsCustomers = Customer::withoutGlobalScopes()
            ->where('store_id', $this->electronicsStore->id)
            ->get();

        foreach ($electronicsCustomers as $customer) {
            $this->customerIds['elec_'.$customer->email] = $customer->id;
        }
    }

    private function seedFashionOrders(): void
    {
        $store = $this->fashionStore;

        // Helper to find variant by product handle and option values
        $findVariant = function (string $productHandle, array $optionValues = []) use ($store): ?ProductVariant {
            $product = Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('handle', $productHandle)
                ->first();

            if (! $product) {
                return null;
            }

            if (empty($optionValues)) {
                return ProductVariant::where('product_id', $product->id)
                    ->where('is_default', true)
                    ->first();
            }

            $query = ProductVariant::where('product_id', $product->id);
            foreach ($optionValues as $value) {
                $query->whereHas('optionValues', fn ($q) => $q->where('value', $value));
            }

            return $query->first();
        };

        $defaultAddress = fn (string $name, string $street, string $zip, string $city) => [
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? '',
            'company' => '',
            'address1' => $street,
            'address2' => '',
            'city' => $city,
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => $zip,
            'phone' => '',
        ];

        $johnAddr = $defaultAddress('John Doe', 'Hauptstrasse 1', '10115', 'Berlin');
        $janeAddr = $defaultAddress('Jane Smith', 'Schillerstrasse 45', '80336', 'Munich');

        // Order #1001
        $this->createOrder($store, [
            'order_number' => '1001',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 4998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 798,
            'total_amount' => 5497,
            'placed_at' => now()->subDays(2),
            'address' => $johnAddr,
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'options' => ['S', 'White'], 'qty' => 2, 'price' => 2499, 'total' => 4998],
            ],
            'payment' => ['reference' => 'mock_test_order1001', 'status' => 'captured', 'amount' => 5497],
        ], $findVariant);

        // Order #1002
        $this->createOrder($store, [
            'order_number' => '1002',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total_amount' => 8997,
            'placed_at' => now()->subDays(10),
            'address' => $johnAddr,
            'lines' => [
                ['handle' => 'organic-hoodie', 'options' => ['M'], 'qty' => 1, 'price' => 5999, 'total' => 5999],
                ['handle' => 'classic-cotton-t-shirt', 'options' => ['L', 'Black'], 'qty' => 1, 'price' => 2499, 'total' => 2499],
            ],
            'payment' => ['reference' => 'mock_test_order1002', 'status' => 'captured', 'amount' => 8997],
            'fulfillment' => [
                'status' => 'delivered',
                'company' => 'DHL',
                'number' => 'DHL1234567890',
                'shipped_at' => now()->subDays(8),
                'all_lines' => true,
            ],
        ], $findVariant);

        // Order #1003
        $this->createOrder($store, [
            'order_number' => '1003',
            'customer_email' => 'jane@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'partial',
            'subtotal_amount' => 11498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1836,
            'total_amount' => 11997,
            'placed_at' => now()->subDays(5),
            'address' => $janeAddr,
            'lines' => [
                ['handle' => 'premium-slim-fit-jeans', 'options' => ['32', 'Blue'], 'qty' => 1, 'price' => 7999, 'total' => 7999],
                ['handle' => 'leather-belt', 'options' => ['L/XL', 'Brown'], 'qty' => 1, 'price' => 3499, 'total' => 3499],
            ],
            'payment' => ['reference' => 'mock_test_order1003', 'status' => 'captured', 'amount' => 11997],
            'fulfillment' => [
                'status' => 'shipped',
                'company' => 'DHL',
                'number' => 'DHL9876543210',
                'shipped_at' => now()->subDays(3),
                'line_indices' => [0], // Only first line (jeans)
            ],
        ], $findVariant);

        // Order #1004 - Cancelled
        $this->createOrder($store, [
            'order_number' => '1004',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'credit_card',
            'status' => 'cancelled',
            'financial_status' => 'refunded',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 2499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 399,
            'total_amount' => 2998,
            'placed_at' => now()->subDays(15),
            'address' => $johnAddr,
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'options' => ['M', 'Navy'], 'qty' => 1, 'price' => 2499, 'total' => 2499],
            ],
            'payment' => ['reference' => 'mock_test_order1004', 'status' => 'refunded', 'amount' => 2998],
            'refund' => ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'reference' => 'mock_re_test_order1004'],
        ], $findVariant);

        // Order #1005 - Bank transfer pending
        $this->createOrder($store, [
            'order_number' => '1005',
            'customer_email' => 'jane@example.com',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 3499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 559,
            'total_amount' => 3998,
            'placed_at' => now()->subHours(2),
            'address' => $janeAddr,
            'lines' => [
                ['handle' => 'leather-belt', 'options' => ['S/M', 'Black'], 'qty' => 1, 'price' => 3499, 'total' => 3499],
            ],
            'payment' => ['reference' => 'mock_test_order1005', 'status' => 'pending', 'amount' => 3998, 'method' => 'bank_transfer'],
        ], $findVariant);

        // Order #1006
        $michaelAddr = $defaultAddress('Michael Brown', 'Musterstrasse 10', '20095', 'Hamburg');
        $this->createOrder($store, [
            'order_number' => '1006',
            'customer_email' => 'michael@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 11999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1916,
            'total_amount' => 12498,
            'placed_at' => now()->subDay(),
            'address' => $michaelAddr,
            'lines' => [
                ['handle' => 'running-sneakers', 'options' => ['EU 42', 'Black'], 'qty' => 1, 'price' => 11999, 'total' => 11999],
            ],
            'payment' => ['reference' => 'mock_test_order1006', 'status' => 'captured', 'amount' => 12498],
        ], $findVariant);

        // Order #1007 - PayPal, fulfilled
        $sarahAddr = $defaultAddress('Sarah Wilson', 'Musterstrasse 13', '70173', 'Stuttgart');
        $this->createOrder($store, [
            'order_number' => '1007',
            'customer_email' => 'sarah@example.com',
            'payment_method' => 'paypal',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 9997,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1596,
            'total_amount' => 10496,
            'placed_at' => now()->subDays(20),
            'address' => $sarahAddr,
            'lines' => [
                ['handle' => 'v-neck-linen-tee', 'options' => ['M', 'Beige'], 'qty' => 2, 'price' => 3499, 'total' => 6998],
                ['handle' => 'wool-scarf', 'options' => ['Grey'], 'qty' => 1, 'price' => 2999, 'total' => 2999],
            ],
            'payment' => ['reference' => 'mock_test_order1007', 'status' => 'captured', 'amount' => 10496, 'method' => 'paypal'],
            'fulfillment' => [
                'status' => 'delivered',
                'company' => 'DHL',
                'number' => 'DHL1112223334',
                'shipped_at' => now()->subDays(18),
                'all_lines' => true,
            ],
        ], $findVariant);

        // Order #1008 - Partial refund
        $davidAddr = $defaultAddress('David Lee', 'Musterstrasse 11', '60311', 'Frankfurt');
        $this->createOrder($store, [
            'order_number' => '1008',
            'customer_email' => 'david@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'partially_refunded',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total_amount' => 8997,
            'placed_at' => now()->subDays(12),
            'address' => $davidAddr,
            'lines' => [
                ['handle' => 'cargo-pants', 'options' => ['32', 'Khaki'], 'qty' => 1, 'price' => 5499, 'total' => 5499],
                ['handle' => 'graphic-print-tee', 'options' => ['L'], 'qty' => 1, 'price' => 2999, 'total' => 2999],
            ],
            'payment' => ['reference' => 'mock_test_order1008', 'status' => 'captured', 'amount' => 8997],
            'fulfillment' => [
                'status' => 'delivered',
                'company' => 'UPS',
                'number' => 'UPS5556667778',
                'shipped_at' => now()->subDays(10),
                'all_lines' => true,
            ],
            'refund' => ['amount' => 2999, 'reason' => 'Item returned', 'reference' => 'mock_re_test_order1008'],
        ], $findVariant);

        // Order #1009
        $emmaAddr = $defaultAddress('Emma Garcia', 'Musterstrasse 12', '50667', 'Cologne');
        $this->createOrder($store, [
            'order_number' => '1009',
            'customer_email' => 'emma@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 4498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 718,
            'total_amount' => 4997,
            'placed_at' => now()->subDays(3),
            'address' => $emmaAddr,
            'lines' => [
                ['handle' => 'canvas-tote-bag', 'options' => ['Natural'], 'qty' => 1, 'price' => 1999, 'total' => 1999],
                ['handle' => 'bucket-hat', 'options' => ['S/M', 'Black'], 'qty' => 1, 'price' => 2499, 'total' => 2499],
            ],
            'payment' => ['reference' => 'mock_test_order1009', 'status' => 'captured', 'amount' => 4997],
        ], $findVariant);

        // Order #1010 - High value PayPal
        $this->createOrder($store, [
            'order_number' => '1010',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'paypal',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 49999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 7983,
            'total_amount' => 50498,
            'placed_at' => now()->subDay(),
            'address' => $johnAddr,
            'lines' => [
                ['handle' => 'cashmere-overcoat', 'options' => ['M', 'Camel'], 'qty' => 1, 'price' => 49999, 'total' => 49999],
            ],
            'payment' => ['reference' => 'mock_test_order1010', 'status' => 'captured', 'amount' => 50498, 'method' => 'paypal'],
        ], $findVariant);

        // Order #1011
        $jamesAddr = $defaultAddress('James Taylor', 'Musterstrasse 14', '40213', 'Dusseldorf');
        $this->createOrder($store, [
            'order_number' => '1011',
            'customer_email' => 'james@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 2799,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 447,
            'total_amount' => 3298,
            'placed_at' => now()->subDays(25),
            'address' => $jamesAddr,
            'lines' => [
                ['handle' => 'striped-polo-shirt', 'options' => ['XL'], 'qty' => 1, 'price' => 2799, 'total' => 2799],
            ],
            'payment' => ['reference' => 'mock_test_order1011', 'status' => 'captured', 'amount' => 3298],
            'fulfillment' => [
                'status' => 'delivered',
                'company' => 'FedEx',
                'number' => 'FX9998887776',
                'shipped_at' => now()->subDays(23),
                'all_lines' => true,
            ],
        ], $findVariant);

        // Order #1012
        $lisaAddr = $defaultAddress('Lisa Anderson', 'Musterstrasse 15', '04109', 'Leipzig');
        $this->createOrder($store, [
            'order_number' => '1012',
            'customer_email' => 'lisa@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total_amount' => 8497,
            'placed_at' => now()->subDays(4),
            'address' => $lisaAddr,
            'lines' => [
                ['handle' => 'chino-shorts', 'options' => ['34', 'Navy'], 'qty' => 2, 'price' => 3999, 'total' => 7998],
            ],
            'payment' => ['reference' => 'mock_test_order1012', 'status' => 'captured', 'amount' => 8497],
        ], $findVariant);

        // Order #1013
        $robertAddr = $defaultAddress('Robert Martinez', 'Musterstrasse 16', '01067', 'Dresden');
        $this->createOrder($store, [
            'order_number' => '1013',
            'customer_email' => 'robert@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total_amount' => 8497,
            'placed_at' => now()->subDay(),
            'address' => $robertAddr,
            'lines' => [
                ['handle' => 'wide-leg-trousers', 'options' => ['M'], 'qty' => 1, 'price' => 4999, 'total' => 4999],
                ['handle' => 'wool-scarf', 'options' => ['Burgundy'], 'qty' => 1, 'price' => 2999, 'total' => 2999],
            ],
            'payment' => ['reference' => 'mock_test_order1013', 'status' => 'captured', 'amount' => 8497],
        ], $findVariant);

        // Order #1014 - Digital product
        $annaAddr = $defaultAddress('Anna Thomas', 'Musterstrasse 17', '90402', 'Nuremberg');
        $this->createOrder($store, [
            'order_number' => '1014',
            'customer_email' => 'anna@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 798,
            'total_amount' => 5000,
            'placed_at' => now()->subDays(14),
            'address' => $annaAddr,
            'lines' => [
                ['handle' => 'gift-card', 'options' => ['50 EUR'], 'qty' => 1, 'price' => 5000, 'total' => 5000],
            ],
            'payment' => ['reference' => 'mock_test_order1014', 'status' => 'captured', 'amount' => 5000],
            'fulfillment' => [
                'status' => 'delivered',
                'company' => null,
                'number' => null,
                'shipped_at' => now()->subDays(14),
                'all_lines' => true,
            ],
        ], $findVariant);

        // Order #1015 - With discount
        $welcome10 = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('code', 'WELCOME10')
            ->first();

        $this->createOrder($store, [
            'order_number' => '1015',
            'customer_email' => 'customer@acme.test',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 5498,
            'discount_amount' => 550,
            'shipping_amount' => 499,
            'tax_amount' => 790,
            'total_amount' => 5447,
            'discount_code' => 'WELCOME10',
            'placed_at' => now(),
            'address' => $johnAddr,
            'lines' => [
                [
                    'handle' => 'classic-cotton-t-shirt',
                    'options' => ['M', 'White'],
                    'qty' => 1,
                    'price' => 2499,
                    'total' => 2499,
                    'discount_amount' => 250,
                    'discount_id' => $welcome10?->id,
                ],
                [
                    'handle' => 'graphic-print-tee',
                    'options' => ['M'],
                    'qty' => 1,
                    'price' => 2999,
                    'total' => 2999,
                    'discount_amount' => 300,
                    'discount_id' => $welcome10?->id,
                ],
            ],
            'payment' => ['reference' => 'mock_test_order1015', 'status' => 'captured', 'amount' => 5447, 'method' => 'bank_transfer'],
        ], $findVariant);
    }

    private function seedElectronicsOrders(): void
    {
        $store = $this->electronicsStore;

        $findVariant = function (string $productHandle, array $optionValues = []) use ($store): ?ProductVariant {
            $product = Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('handle', $productHandle)
                ->first();

            if (! $product) {
                return null;
            }

            if (empty($optionValues)) {
                return ProductVariant::where('product_id', $product->id)
                    ->where('is_default', true)
                    ->first();
            }

            $query = ProductVariant::where('product_id', $product->id);
            foreach ($optionValues as $value) {
                $query->whereHas('optionValues', fn ($q) => $q->where('value', $value));
            }

            return $query->first();
        };

        $techAddr = [
            'first_name' => 'Tech', 'last_name' => 'Fan',
            'company' => '', 'address1' => 'Techstrasse 1', 'address2' => '',
            'city' => 'Berlin', 'province' => '', 'province_code' => '',
            'country' => 'Germany', 'country_code' => 'DE', 'zip' => '10115', 'phone' => '',
        ];

        $gadgetAddr = [
            'first_name' => 'Gadget', 'last_name' => 'Lover',
            'company' => '', 'address1' => 'Techstrasse 2', 'address2' => '',
            'city' => 'Berlin', 'province' => '', 'province_code' => '',
            'country' => 'Germany', 'country_code' => 'DE', 'zip' => '10115', 'phone' => '',
        ];

        // Order #5001
        $this->createOrder($store, [
            'order_number' => '5001',
            'customer_email' => 'techfan@example.com',
            'customer_key' => 'elec_techfan@example.com',
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 121298,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 121298,
            'placed_at' => now()->subDays(7),
            'address' => $techAddr,
            'lines' => [
                ['handle' => 'pro-laptop-15', 'options' => ['512GB'], 'qty' => 1, 'price' => 119999, 'total' => 119999],
                ['handle' => 'usb-c-cable-2m', 'options' => [], 'qty' => 1, 'price' => 1299, 'total' => 1299],
            ],
            'payment' => ['reference' => 'mock_test_order5001', 'status' => 'captured', 'amount' => 121298],
            'fulfillment' => [
                'status' => 'delivered',
                'company' => 'DHL',
                'number' => 'DHL5001TRACK',
                'shipped_at' => now()->subDays(5),
                'all_lines' => true,
            ],
        ], $findVariant);

        // Order #5002
        $this->createOrder($store, [
            'order_number' => '5002',
            'customer_email' => 'gadgetlover@example.com',
            'customer_key' => 'elec_gadgetlover@example.com',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 14999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 14999,
            'placed_at' => now()->subDays(3),
            'address' => $gadgetAddr,
            'lines' => [
                ['handle' => 'wireless-headphones', 'options' => ['Black'], 'qty' => 1, 'price' => 14999, 'total' => 14999],
            ],
            'payment' => ['reference' => 'mock_test_order5002', 'status' => 'captured', 'amount' => 14999],
        ], $findVariant);

        // Order #5003
        $this->createOrder($store, [
            'order_number' => '5003',
            'customer_email' => 'techfan@example.com',
            'customer_key' => 'elec_techfan@example.com',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 4999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 4999,
            'placed_at' => now()->subDay(),
            'address' => $techAddr,
            'lines' => [
                ['handle' => 'monitor-stand', 'options' => [], 'qty' => 1, 'price' => 4999, 'total' => 4999],
            ],
            'payment' => ['reference' => 'mock_test_order5003', 'status' => 'pending', 'amount' => 4999, 'method' => 'bank_transfer'],
        ], $findVariant);
    }

    private function createOrder(Store $store, array $data, callable $findVariant): void
    {
        $customerKey = $data['customer_key'] ?? $data['customer_email'];
        $customerId = $this->customerIds[$customerKey] ?? null;

        $order = Order::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'order_number' => $data['order_number']],
            [
                'customer_id' => $customerId,
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'financial_status' => $data['financial_status'],
                'fulfillment_status' => $data['fulfillment_status'],
                'currency' => 'EUR',
                'subtotal_amount' => $data['subtotal_amount'],
                'discount_amount' => $data['discount_amount'],
                'shipping_amount' => $data['shipping_amount'],
                'tax_amount' => $data['tax_amount'],
                'total_amount' => $data['total_amount'],
                'email' => $data['customer_email'],
                'discount_code' => $data['discount_code'] ?? null,
                'billing_address_json' => $data['address'],
                'shipping_address_json' => $data['address'],
                'placed_at' => $data['placed_at'],
            ],
        );

        // Create order lines
        $orderLines = [];
        foreach ($data['lines'] as $lineData) {
            $variant = $findVariant($lineData['handle'], $lineData['options']);
            $product = $variant ? Product::withoutGlobalScopes()->find($variant->product_id) : null;

            $orderLine = OrderLine::firstOrCreate(
                ['order_id' => $order->id, 'variant_id' => $variant?->id, 'title_snapshot' => $product?->title ?? $lineData['handle']],
                [
                    'product_id' => $product?->id,
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => $lineData['qty'],
                    'unit_price_amount' => $lineData['price'],
                    'discount_amount' => $lineData['discount_amount'] ?? 0,
                    'total_amount' => $lineData['total'],
                ],
            );

            $orderLines[] = $orderLine;
        }

        // Create payment
        if (isset($data['payment'])) {
            $paymentData = $data['payment'];
            Payment::firstOrCreate(
                ['order_id' => $order->id, 'reference' => $paymentData['reference']],
                [
                    'method' => $paymentData['method'] ?? $data['payment_method'],
                    'status' => $paymentData['status'],
                    'amount' => $paymentData['amount'],
                    'currency' => 'EUR',
                    'captured_at' => $paymentData['status'] === 'captured' ? $data['placed_at'] : null,
                ],
            );
        }

        // Create fulfillment
        if (isset($data['fulfillment'])) {
            $fData = $data['fulfillment'];
            $fulfillment = Fulfillment::firstOrCreate(
                ['order_id' => $order->id, 'tracking_number' => $fData['number']],
                [
                    'status' => $fData['status'],
                    'tracking_company' => $fData['company'],
                    'tracking_url' => $fData['number'] ? 'https://tracking.example.com/'.$fData['number'] : null,
                    'shipped_at' => $fData['shipped_at'],
                    'delivered_at' => $fData['status'] === 'delivered' ? $fData['shipped_at']?->addDays(2) : null,
                ],
            );

            // Create fulfillment lines
            if (! empty($fData['all_lines'])) {
                foreach ($orderLines as $orderLine) {
                    FulfillmentLine::firstOrCreate(
                        ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $orderLine->id],
                        ['quantity' => $orderLine->quantity],
                    );
                }
            } elseif (! empty($fData['line_indices'])) {
                foreach ($fData['line_indices'] as $index) {
                    if (isset($orderLines[$index])) {
                        FulfillmentLine::firstOrCreate(
                            ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $orderLines[$index]->id],
                            ['quantity' => $orderLines[$index]->quantity],
                        );
                    }
                }
            }
        }

        // Create refund
        if (isset($data['refund'])) {
            $rData = $data['refund'];
            $payment = Payment::where('order_id', $order->id)->first();

            if ($payment) {
                Refund::firstOrCreate(
                    ['order_id' => $order->id, 'reference' => $rData['reference']],
                    [
                        'payment_id' => $payment->id,
                        'amount' => $rData['amount'],
                        'currency' => 'EUR',
                        'reason' => $rData['reason'],
                        'status' => 'processed',
                        'processed_at' => now(),
                    ],
                );
            }
        }
    }
}
