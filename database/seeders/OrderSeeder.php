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
    private Store $fashion;

    private Store $electronics;

    public function run(): void
    {
        $this->fashion = Store::where('slug', 'acme-fashion')->firstOrFail();
        $this->electronics = Store::where('slug', 'acme-electronics')->firstOrFail();

        $this->seedFashionOrders();
        $this->seedElectronicsOrders();
    }

    private function seedFashionOrders(): void
    {
        $store = $this->fashion;
        $johnDoe = Customer::where('store_id', $store->id)->where('email', 'customer@acme.test')->firstOrFail();
        $janeSmith = Customer::where('store_id', $store->id)->where('email', 'jane@example.com')->firstOrFail();
        $michael = Customer::where('store_id', $store->id)->where('email', 'michael@example.com')->firstOrFail();
        $sarah = Customer::where('store_id', $store->id)->where('email', 'sarah@example.com')->firstOrFail();
        $david = Customer::where('store_id', $store->id)->where('email', 'david@example.com')->firstOrFail();
        $emma = Customer::where('store_id', $store->id)->where('email', 'emma@example.com')->firstOrFail();
        $james = Customer::where('store_id', $store->id)->where('email', 'james@example.com')->firstOrFail();
        $lisa = Customer::where('store_id', $store->id)->where('email', 'lisa@example.com')->firstOrFail();
        $robert = Customer::where('store_id', $store->id)->where('email', 'robert@example.com')->firstOrFail();
        $anna = Customer::where('store_id', $store->id)->where('email', 'anna@example.com')->firstOrFail();

        $johnAddress = $this->addressJson('John', 'Doe', 'Hauptstrasse 1', 'Berlin', 'DE', '10115');
        $janeAddress = $this->addressJson('Jane', 'Smith', 'Schillerstrasse 45', 'Munich', 'DE', '80336');

        // Order #1001
        $this->createOrder($store, $johnDoe, '#1001', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(2),
            'subtotal' => 4998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 798,
            'total' => 5497,
            'address' => $johnAddress,
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'option_values' => ['S', 'White'], 'qty' => 2, 'unit_price' => 2499],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1001', 'status' => PaymentStatus::Captured, 'amount' => 5497],
        ]);

        // Order #1002
        $this->createOrder($store, $johnDoe, '#1002', [
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(10),
            'subtotal' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total' => 8997,
            'address' => $johnAddress,
            'lines' => [
                ['handle' => 'organic-hoodie', 'option_values' => ['M'], 'qty' => 1, 'unit_price' => 5999],
                ['handle' => 'classic-cotton-t-shirt', 'option_values' => ['L', 'Black'], 'qty' => 1, 'unit_price' => 2499],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1002', 'status' => PaymentStatus::Captured, 'amount' => 8997],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => 'DHL',
                'tracking_number' => 'DHL1234567890',
                'shipped_at' => now()->subDays(8),
                'all_lines' => true,
            ],
        ]);

        // Order #1003
        $this->createOrder($store, $janeSmith, '#1003', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Partial,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(5),
            'subtotal' => 11498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1836,
            'total' => 11997,
            'address' => $janeAddress,
            'lines' => [
                ['handle' => 'premium-slim-fit-jeans', 'option_values' => ['32', 'Blue'], 'qty' => 1, 'unit_price' => 7999],
                ['handle' => 'leather-belt', 'option_values' => ['L/XL', 'Brown'], 'qty' => 1, 'unit_price' => 3499],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1003', 'status' => PaymentStatus::Captured, 'amount' => 11997],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Shipped,
                'tracking_company' => 'DHL',
                'tracking_number' => 'DHL9876543210',
                'shipped_at' => now()->subDays(3),
                'line_indices' => [0],
            ],
        ]);

        // Order #1004
        $this->createOrder($store, $johnDoe, '#1004', [
            'status' => OrderStatus::Cancelled,
            'financial_status' => FinancialStatus::Refunded,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(15),
            'subtotal' => 2499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 399,
            'total' => 2998,
            'address' => $johnAddress,
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'option_values' => ['M', 'Navy'], 'qty' => 1, 'unit_price' => 2499],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1004', 'status' => PaymentStatus::Refunded, 'amount' => 2998],
            'refund' => ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'status' => RefundStatus::Processed, 'provider_refund_id' => 'mock_re_test_order1004'],
        ]);

        // Order #1005
        $this->createOrder($store, $janeSmith, '#1005', [
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'bank_transfer',
            'placed_at' => now()->subHours(2),
            'subtotal' => 3499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 559,
            'total' => 3998,
            'address' => $janeAddress,
            'lines' => [
                ['handle' => 'leather-belt', 'option_values' => ['S/M', 'Black'], 'qty' => 1, 'unit_price' => 3499],
            ],
            'payment' => ['method' => PaymentMethod::BankTransfer, 'id' => 'mock_test_order1005', 'status' => PaymentStatus::Pending, 'amount' => 3998],
        ]);

        // Order #1006
        $this->createOrder($store, $michael, '#1006', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDay(),
            'subtotal' => 11999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1916,
            'total' => 12498,
            'address' => $this->addressForCustomer($michael),
            'lines' => [
                ['handle' => 'running-sneakers', 'option_values' => ['EU 42', 'Black'], 'qty' => 1, 'unit_price' => 11999],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1006', 'status' => PaymentStatus::Captured, 'amount' => 12498],
        ]);

        // Order #1007
        $this->createOrder($store, $sarah, '#1007', [
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => 'paypal',
            'placed_at' => now()->subDays(20),
            'subtotal' => 9997,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1596,
            'total' => 10496,
            'address' => $this->addressForCustomer($sarah),
            'lines' => [
                ['handle' => 'v-neck-linen-tee', 'option_values' => ['M', 'Beige'], 'qty' => 2, 'unit_price' => 3499],
                ['handle' => 'wool-scarf', 'option_values' => ['Grey'], 'qty' => 1, 'unit_price' => 2999],
            ],
            'payment' => ['method' => PaymentMethod::Paypal, 'id' => 'mock_test_order1007', 'status' => PaymentStatus::Captured, 'amount' => 10496],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => 'DHL',
                'tracking_number' => 'DHL1112223334',
                'shipped_at' => now()->subDays(18),
                'all_lines' => true,
            ],
        ]);

        // Order #1008
        $this->createOrder($store, $david, '#1008', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::PartiallyRefunded,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(12),
            'subtotal' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total' => 8997,
            'address' => $this->addressForCustomer($david),
            'lines' => [
                ['handle' => 'cargo-pants', 'option_values' => ['32', 'Khaki'], 'qty' => 1, 'unit_price' => 5499],
                ['handle' => 'graphic-print-tee', 'option_values' => ['L'], 'qty' => 1, 'unit_price' => 2999],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1008', 'status' => PaymentStatus::Captured, 'amount' => 8997],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => 'UPS',
                'tracking_number' => 'UPS5556667778',
                'shipped_at' => now()->subDays(10),
                'all_lines' => true,
            ],
            'refund' => ['amount' => 2999, 'reason' => 'Item returned', 'status' => RefundStatus::Processed, 'provider_refund_id' => 'mock_re_test_order1008'],
        ]);

        // Order #1009
        $this->createOrder($store, $emma, '#1009', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(3),
            'subtotal' => 4498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 718,
            'total' => 4997,
            'address' => $this->addressForCustomer($emma),
            'lines' => [
                ['handle' => 'canvas-tote-bag', 'option_values' => ['Natural'], 'qty' => 1, 'unit_price' => 1999],
                ['handle' => 'bucket-hat', 'option_values' => ['S/M', 'Black'], 'qty' => 1, 'unit_price' => 2499],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1009', 'status' => PaymentStatus::Captured, 'amount' => 4997],
        ]);

        // Order #1010
        $this->createOrder($store, $johnDoe, '#1010', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'paypal',
            'placed_at' => now()->subDay(),
            'subtotal' => 49999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 7983,
            'total' => 50498,
            'address' => $johnAddress,
            'lines' => [
                ['handle' => 'cashmere-overcoat', 'option_values' => ['M', 'Camel'], 'qty' => 1, 'unit_price' => 49999],
            ],
            'payment' => ['method' => PaymentMethod::Paypal, 'id' => 'mock_test_order1010', 'status' => PaymentStatus::Captured, 'amount' => 50498],
        ]);

        // Order #1011
        $this->createOrder($store, $james, '#1011', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(25),
            'subtotal' => 2799,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 447,
            'total' => 3298,
            'address' => $this->addressForCustomer($james),
            'lines' => [
                ['handle' => 'striped-polo-shirt', 'option_values' => ['XL'], 'qty' => 1, 'unit_price' => 2799],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1011', 'status' => PaymentStatus::Captured, 'amount' => 3298],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => 'FedEx',
                'tracking_number' => 'FX9998887776',
                'shipped_at' => now()->subDays(23),
                'all_lines' => true,
            ],
        ]);

        // Order #1012
        $this->createOrder($store, $lisa, '#1012', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(4),
            'subtotal' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total' => 8497,
            'address' => $this->addressForCustomer($lisa),
            'lines' => [
                ['handle' => 'chino-shorts', 'option_values' => ['34', 'Navy'], 'qty' => 2, 'unit_price' => 3999],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1012', 'status' => PaymentStatus::Captured, 'amount' => 8497],
        ]);

        // Order #1013
        $this->createOrder($store, $robert, '#1013', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDay(),
            'subtotal' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total' => 8497,
            'address' => $this->addressForCustomer($robert),
            'lines' => [
                ['handle' => 'wide-leg-trousers', 'option_values' => ['M'], 'qty' => 1, 'unit_price' => 4999],
                ['handle' => 'wool-scarf', 'option_values' => ['Burgundy'], 'qty' => 1, 'unit_price' => 2999],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1013', 'status' => PaymentStatus::Captured, 'amount' => 8497],
        ]);

        // Order #1014
        $this->createOrder($store, $anna, '#1014', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(14),
            'subtotal' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 798,
            'total' => 5000,
            'address' => $this->addressForCustomer($anna),
            'lines' => [
                ['handle' => 'gift-card', 'option_values' => ['50 EUR'], 'qty' => 1, 'unit_price' => 5000],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order1014', 'status' => PaymentStatus::Captured, 'amount' => 5000],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => null,
                'tracking_number' => null,
                'shipped_at' => now()->subDays(14),
                'all_lines' => true,
            ],
        ]);

        // Order #1015
        $welcome10 = Discount::where('store_id', $store->id)->where('code', 'WELCOME10')->firstOrFail();
        $this->createOrder($store, $johnDoe, '#1015', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'bank_transfer',
            'placed_at' => now(),
            'subtotal' => 5498,
            'discount_amount' => 550,
            'discount_code' => 'WELCOME10',
            'shipping_amount' => 499,
            'tax_amount' => 790,
            'total' => 5447,
            'address' => $johnAddress,
            'lines' => [
                ['handle' => 'classic-cotton-t-shirt', 'option_values' => ['M', 'White'], 'qty' => 1, 'unit_price' => 2499, 'discount_allocation' => ['discount_id' => $welcome10->id, 'amount' => 250]],
                ['handle' => 'graphic-print-tee', 'option_values' => ['M'], 'qty' => 1, 'unit_price' => 2999, 'discount_allocation' => ['discount_id' => $welcome10->id, 'amount' => 300]],
            ],
            'payment' => ['method' => PaymentMethod::BankTransfer, 'id' => 'mock_test_order1015', 'status' => PaymentStatus::Captured, 'amount' => 5447],
        ]);
    }

    private function seedElectronicsOrders(): void
    {
        $store = $this->electronics;
        $techfan = Customer::where('store_id', $store->id)->where('email', 'techfan@example.com')->firstOrFail();
        $gadgetlover = Customer::where('store_id', $store->id)->where('email', 'gadgetlover@example.com')->firstOrFail();
        $techAddress = $this->addressForCustomer($techfan);
        $gadgetAddress = $this->addressForCustomer($gadgetlover);

        // Order #5001
        $this->createOrder($store, $techfan, '#5001', [
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(7),
            'subtotal' => 121298,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 19373,
            'total' => 121298,
            'address' => $techAddress,
            'lines' => [
                ['handle' => 'pro-laptop-15', 'option_values' => ['512GB'], 'qty' => 1, 'unit_price' => 119999],
                ['handle' => 'usb-c-cable-2m', 'option_values' => [], 'qty' => 1, 'unit_price' => 1299],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order5001', 'status' => PaymentStatus::Captured, 'amount' => 121298],
            'fulfillment' => [
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => 'DHL',
                'tracking_number' => 'DHL5551112223',
                'shipped_at' => now()->subDays(5),
                'all_lines' => true,
            ],
        ]);

        // Order #5002
        $this->createOrder($store, $gadgetlover, '#5002', [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'credit_card',
            'placed_at' => now()->subDays(2),
            'subtotal' => 14999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 2396,
            'total' => 14999,
            'address' => $gadgetAddress,
            'lines' => [
                ['handle' => 'wireless-headphones', 'option_values' => ['Black'], 'qty' => 1, 'unit_price' => 14999],
            ],
            'payment' => ['method' => PaymentMethod::CreditCard, 'id' => 'mock_test_order5002', 'status' => PaymentStatus::Captured, 'amount' => 14999],
        ]);

        // Order #5003
        $this->createOrder($store, $techfan, '#5003', [
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => 'bank_transfer',
            'placed_at' => now()->subDay(),
            'subtotal' => 4999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 799,
            'total' => 4999,
            'address' => $techAddress,
            'lines' => [
                ['handle' => 'monitor-stand', 'option_values' => [], 'qty' => 1, 'unit_price' => 4999],
            ],
            'payment' => ['method' => PaymentMethod::BankTransfer, 'id' => 'mock_test_order5003', 'status' => PaymentStatus::Pending, 'amount' => 4999],
        ]);
    }

    private function createOrder(Store $store, Customer $customer, string $orderNumber, array $data): void
    {
        if (Order::where('store_id', $store->id)->where('order_number', $orderNumber)->exists()) {
            return;
        }

        $address = $data['address'];

        $order = Order::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'email' => $customer->email,
            'status' => $data['status'],
            'financial_status' => $data['financial_status'],
            'fulfillment_status' => $data['fulfillment_status'],
            'currency' => 'EUR',
            'subtotal' => $data['subtotal'],
            'discount_amount' => $data['discount_amount'],
            'shipping_amount' => $data['shipping_amount'],
            'tax_amount' => $data['tax_amount'],
            'total' => $data['total'],
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'discount_code' => $data['discount_code'] ?? null,
            'payment_method' => $data['payment_method'],
            'placed_at' => $data['placed_at'],
        ]);

        $orderLines = [];
        foreach ($data['lines'] as $lineData) {
            $product = Product::where('store_id', $store->id)->where('handle', $lineData['handle'])->firstOrFail();
            $variant = $this->findVariant($product, $lineData['option_values']);

            $variantTitle = implode(' / ', $lineData['option_values']);

            $orderLine = OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $product->title,
                'sku_snapshot' => $variant->sku,
                'variant_title_snapshot' => $variantTitle ?: 'Default',
                'quantity' => $lineData['qty'],
                'unit_price' => $lineData['unit_price'],
                'subtotal' => $lineData['unit_price'] * $lineData['qty'],
                'total' => $lineData['unit_price'] * $lineData['qty'],
                'requires_shipping' => $variant->requires_shipping,
            ]);
            $orderLines[] = $orderLine;
        }

        $paymentData = $data['payment'];
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => $paymentData['method'],
            'provider' => 'mock',
            'provider_payment_id' => $paymentData['id'],
            'amount' => $paymentData['amount'],
            'currency' => 'EUR',
            'status' => $paymentData['status'],
            'captured_at' => $paymentData['status'] === PaymentStatus::Captured ? $order->placed_at : null,
        ]);

        if (isset($data['fulfillment'])) {
            $fData = $data['fulfillment'];
            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'tracking_company' => $fData['tracking_company'],
                'tracking_number' => $fData['tracking_number'],
                'tracking_url' => $fData['tracking_number'] ? 'https://tracking.example.com/'.$fData['tracking_number'] : null,
                'status' => $fData['status'],
                'shipped_at' => $fData['shipped_at'],
                'delivered_at' => $fData['status'] === FulfillmentShipmentStatus::Delivered ? $fData['shipped_at']->addDays(2) : null,
            ]);

            if ($fData['all_lines'] ?? false) {
                foreach ($orderLines as $line) {
                    FulfillmentLine::create([
                        'fulfillment_id' => $fulfillment->id,
                        'order_line_id' => $line->id,
                        'quantity' => $line->quantity,
                    ]);
                }
            } elseif (isset($fData['line_indices'])) {
                foreach ($fData['line_indices'] as $idx) {
                    FulfillmentLine::create([
                        'fulfillment_id' => $fulfillment->id,
                        'order_line_id' => $orderLines[$idx]->id,
                        'quantity' => $orderLines[$idx]->quantity,
                    ]);
                }
            }
        }

        if (isset($data['refund'])) {
            $rData = $data['refund'];
            Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $rData['amount'],
                'reason' => $rData['reason'],
                'status' => $rData['status'],
                'processed_at' => $rData['status'] === RefundStatus::Processed ? now() : null,
            ]);
        }
    }

    private function findVariant(Product $product, array $optionValues): ProductVariant
    {
        if (empty($optionValues)) {
            return $product->variants()->where('is_default', true)->firstOrFail();
        }

        $query = $product->variants();
        foreach ($optionValues as $value) {
            $query->whereHas('optionValues', function ($q) use ($value): void {
                $q->where('value', $value);
            });
        }

        return $query->firstOrFail();
    }

    private function addressJson(string $firstName, string $lastName, string $address1, string $city, string $countryCode, string $zip): array
    {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => '',
            'address1' => $address1,
            'address2' => '',
            'city' => $city,
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => $countryCode,
            'zip' => $zip,
            'phone' => '',
        ];
    }

    private function addressForCustomer(Customer $customer): array
    {
        $addr = $customer->addresses()->where('is_default', true)->first();
        if ($addr) {
            return [
                'first_name' => $addr->first_name,
                'last_name' => $addr->last_name,
                'company' => $addr->company ?? '',
                'address1' => $addr->address1,
                'address2' => $addr->address2 ?? '',
                'city' => $addr->city,
                'province' => $addr->province ?? '',
                'province_code' => $addr->province_code ?? '',
                'country' => $addr->country ?? 'Germany',
                'country_code' => $addr->country_code ?? 'DE',
                'zip' => $addr->zip,
                'phone' => $addr->phone ?? '',
            ];
        }

        return $this->addressJson($customer->first_name, $customer->last_name, 'Musterstrasse 1', 'Berlin', 'DE', '10115');
    }
}
