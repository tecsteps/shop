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
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');
        $customers = Customer::withoutGlobalScopes()->whereIn('store_id', $stores->pluck('id'))->get()->keyBy('email');
        $products = Product::withoutGlobalScopes()->with('variants')->whereIn('store_id', $stores->pluck('id'))->get()->keyBy('handle');
        $discount = \App\Models\Discount::withoutGlobalScopes()->where('store_id', $stores['acme-fashion']->getKey())->where('code', 'WELCOME10')->firstOrFail();

        foreach ($this->fashionOrders($discount->getKey()) as $orderData) {
            $this->seedOrder($orderData, $customers, $products);
        }

        foreach ($this->electronicsOrders() as $orderData) {
            $this->seedOrder($orderData, $customers, $products);
        }
    }

    private function seedOrder(array $orderData, $customers, $products): void
    {
        $customer = $customers->get($orderData['customer']);
        $address = $customer?->addresses()->where('is_default', true)->first()?->address_json;

        $order = Order::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $orderData['store_id'], 'order_number' => $orderData['order_number']],
            ['customer_id' => $customer?->getKey(), 'currency' => 'EUR', 'status' => $orderData['status'], 'financial_status' => $orderData['financial_status'], 'fulfillment_status' => $orderData['fulfillment_status'], 'payment_method' => $orderData['payment_method'], 'email' => $customer?->email ?? $orderData['customer'], 'shipping_address_json' => $address, 'billing_address_json' => $address, 'subtotal_amount' => $orderData['subtotal_amount'], 'discount_amount' => $orderData['discount_amount'], 'shipping_amount' => $orderData['shipping_amount'], 'tax_amount' => $orderData['tax_amount'], 'total_amount' => $orderData['total_amount'], 'placed_at' => $orderData['placed_at'], 'metadata' => ['seed_fixture' => true]],
        );

        foreach ($order->fulfillments as $fulfillment) {
            $fulfillment->lines()->delete();
        }
        $order->fulfillments()->delete();
        $order->refunds()->delete();
        $order->payments()->delete();
        $order->lines()->delete();

        $lineModels = [];
        foreach ($orderData['lines'] as $lineData) {
            $product = $products->get($lineData['handle']);
            $variant = $product?->variants->firstWhere('title', $lineData['variant']) ?? $product?->variants->first();
            $discountAmount = $lineData['discount_amount'] ?? 0;
            $subtotal = $variant->price_amount * $lineData['quantity'];

            $lineModels[] = $order->lines()->create([
                'product_id' => $product->getKey(),
                'variant_id' => $variant->getKey(),
                'product_title' => $product->title,
                'title_snapshot' => $product->title,
                'variant_title' => $variant->title,
                'sku' => $variant->sku,
                'sku_snapshot' => $variant->sku,
                'quantity' => $lineData['quantity'],
                'unit_price_amount' => $variant->price_amount,
                'line_subtotal_amount' => $subtotal,
                'line_discount_amount' => $discountAmount,
                'line_total_amount' => $subtotal - $discountAmount,
                'total_amount' => $subtotal - $discountAmount,
                'tax_lines_json' => [],
                'discount_allocations_json' => isset($lineData['discount_amount']) ? [['discount_id' => $lineData['discount_id'], 'amount' => $discountAmount]] : [],
            ]);
        }

        $payment = $order->payments()->create(['provider' => 'mock', 'provider_payment_id' => $orderData['provider_payment_id'], 'method' => $orderData['payment_method'], 'status' => $orderData['payment_status'], 'amount' => $orderData['total_amount'], 'currency' => 'EUR']);

        if (isset($orderData['refund'])) {
            $refundLines = [];
            foreach ($orderData['refund']['line_indexes'] as $lineIndex) {
                $refundLines[] = ['order_line_id' => $lineModels[$lineIndex]->getKey(), 'quantity' => $orderData['lines'][$lineIndex]['quantity']];
            }

            $order->refunds()->create(['payment_id' => $payment->getKey(), 'amount' => $orderData['refund']['amount'], 'reason' => $orderData['refund']['reason'], 'status' => RefundStatus::Processed, 'restock' => false, 'provider_refund_id' => $orderData['refund']['provider_refund_id'], 'lines_json' => $refundLines]);
        }

        if (isset($orderData['fulfillment'])) {
            $fulfillmentData = $orderData['fulfillment'];
            $fulfillment = $order->fulfillments()->create(['status' => $fulfillmentData['status'], 'tracking_company' => $fulfillmentData['tracking_company'], 'tracking_number' => $fulfillmentData['tracking_number'], 'tracking_url' => $fulfillmentData['tracking_number'] === null ? null : 'https://tracking.example.com/'.$fulfillmentData['tracking_number'], 'shipped_at' => $fulfillmentData['shipped_at'], 'delivered_at' => $fulfillmentData['delivered_at'], 'fulfilled_at' => $fulfillmentData['status'] === FulfillmentShipmentStatus::Delivered ? $fulfillmentData['delivered_at'] : null]);

            foreach ($fulfillmentData['line_indexes'] as $lineIndex) {
                $fulfillment->lines()->create(['order_line_id' => $lineModels[$lineIndex]->getKey(), 'quantity' => $orderData['lines'][$lineIndex]['quantity']]);
            }
        }
    }

    private function fashionOrders(int $discountId): array
    {
        $base = ['store_id' => Store::query()->where('handle', 'acme-fashion')->value('id')];

        return [
            array_merge($base, ['order_number' => '#1001', 'customer' => 'customer@acme.test', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDays(2), 'lines' => [['handle' => 'classic-cotton-t-shirt', 'variant' => 'S / White', 'quantity' => 2]], 'subtotal_amount' => 4998, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 798, 'total_amount' => 5497, 'provider_payment_id' => 'mock_test_order1001', 'payment_status' => PaymentStatus::Captured]),
            array_merge($base, ['order_number' => '#1002', 'customer' => 'customer@acme.test', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Fulfilled, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => now()->subDays(10), 'lines' => [['handle' => 'organic-hoodie', 'variant' => 'M', 'quantity' => 1], ['handle' => 'classic-cotton-t-shirt', 'variant' => 'L / Black', 'quantity' => 1]], 'subtotal_amount' => 8498, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1357, 'total_amount' => 8997, 'provider_payment_id' => 'mock_test_order1002', 'payment_status' => PaymentStatus::Captured, 'fulfillment' => ['status' => FulfillmentShipmentStatus::Delivered, 'tracking_company' => 'DHL', 'tracking_number' => 'DHL1234567890', 'shipped_at' => now()->subDays(8), 'delivered_at' => now()->subDays(2), 'line_indexes' => [0, 1]]]),
            array_merge($base, ['order_number' => '#1003', 'customer' => 'jane@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Partial, 'placed_at' => now()->subDays(5), 'lines' => [['handle' => 'premium-slim-fit-jeans', 'variant' => '32 / Blue', 'quantity' => 1], ['handle' => 'leather-belt', 'variant' => 'L/XL / Brown', 'quantity' => 1]], 'subtotal_amount' => 11498, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1836, 'total_amount' => 11997, 'provider_payment_id' => 'mock_test_order1003', 'payment_status' => PaymentStatus::Captured, 'fulfillment' => ['status' => FulfillmentShipmentStatus::Shipped, 'tracking_company' => 'DHL', 'tracking_number' => 'DHL9876543210', 'shipped_at' => now()->subDays(3), 'delivered_at' => null, 'line_indexes' => [0]]]),
            array_merge($base, ['order_number' => '#1004', 'customer' => 'customer@acme.test', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Cancelled, 'financial_status' => FinancialStatus::Refunded, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDays(15), 'lines' => [['handle' => 'classic-cotton-t-shirt', 'variant' => 'M / Navy', 'quantity' => 1]], 'subtotal_amount' => 2499, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 399, 'total_amount' => 2998, 'provider_payment_id' => 'mock_test_order1004', 'payment_status' => PaymentStatus::Refunded, 'refund' => ['amount' => 2998, 'reason' => 'Customer requested cancellation', 'provider_refund_id' => 'mock_re_test_order1004', 'line_indexes' => [0]]]),
            array_merge($base, ['order_number' => '#1005', 'customer' => 'jane@example.com', 'payment_method' => PaymentMethod::BankTransfer, 'status' => OrderStatus::Pending, 'financial_status' => FinancialStatus::Pending, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subHours(2), 'lines' => [['handle' => 'leather-belt', 'variant' => 'S/M / Black', 'quantity' => 1]], 'subtotal_amount' => 3499, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 559, 'total_amount' => 3998, 'provider_payment_id' => 'mock_test_order1005', 'payment_status' => PaymentStatus::Pending]),
            array_merge($base, ['order_number' => '#1006', 'customer' => 'michael@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDay(), 'lines' => [['handle' => 'running-sneakers', 'variant' => 'EU 42 / Black', 'quantity' => 1]], 'subtotal_amount' => 11999, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1916, 'total_amount' => 12498, 'provider_payment_id' => 'mock_test_order1006', 'payment_status' => PaymentStatus::Captured]),
            array_merge($base, ['order_number' => '#1007', 'customer' => 'sarah@example.com', 'payment_method' => PaymentMethod::Paypal, 'status' => OrderStatus::Fulfilled, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => now()->subDays(20), 'lines' => [['handle' => 'v-neck-linen-tee', 'variant' => 'M / Beige', 'quantity' => 2], ['handle' => 'wool-scarf', 'variant' => 'Grey', 'quantity' => 1]], 'subtotal_amount' => 9997, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1596, 'total_amount' => 10496, 'provider_payment_id' => 'mock_test_order1007', 'payment_status' => PaymentStatus::Captured, 'fulfillment' => ['status' => FulfillmentShipmentStatus::Delivered, 'tracking_company' => 'DHL', 'tracking_number' => 'DHL1112223334', 'shipped_at' => now()->subDays(18), 'delivered_at' => now()->subDays(12), 'line_indexes' => [0, 1]]]),
            array_merge($base, ['order_number' => '#1008', 'customer' => 'david@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::PartiallyRefunded, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => now()->subDays(12), 'lines' => [['handle' => 'cargo-pants', 'variant' => '32 / Khaki', 'quantity' => 1], ['handle' => 'graphic-print-tee', 'variant' => 'L', 'quantity' => 1]], 'subtotal_amount' => 8498, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1357, 'total_amount' => 8997, 'provider_payment_id' => 'mock_test_order1008', 'payment_status' => PaymentStatus::Captured, 'refund' => ['amount' => 2999, 'reason' => 'Item returned', 'provider_refund_id' => 'mock_re_test_order1008', 'line_indexes' => [1]], 'fulfillment' => ['status' => FulfillmentShipmentStatus::Delivered, 'tracking_company' => 'UPS', 'tracking_number' => 'UPS5556667778', 'shipped_at' => now()->subDays(10), 'delivered_at' => now()->subDays(4), 'line_indexes' => [0, 1]]]),
            array_merge($base, ['order_number' => '#1009', 'customer' => 'emma@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDays(3), 'lines' => [['handle' => 'canvas-tote-bag', 'variant' => 'Natural', 'quantity' => 1], ['handle' => 'bucket-hat', 'variant' => 'S/M / Black', 'quantity' => 1]], 'subtotal_amount' => 4498, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 718, 'total_amount' => 4997, 'provider_payment_id' => 'mock_test_order1009', 'payment_status' => PaymentStatus::Captured]),
            array_merge($base, ['order_number' => '#1010', 'customer' => 'customer@acme.test', 'payment_method' => PaymentMethod::Paypal, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDay(), 'lines' => [['handle' => 'cashmere-overcoat', 'variant' => 'M / Camel', 'quantity' => 1]], 'subtotal_amount' => 49999, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 7983, 'total_amount' => 50498, 'provider_payment_id' => 'mock_test_order1010', 'payment_status' => PaymentStatus::Captured]),
            array_merge($base, ['order_number' => '#1011', 'customer' => 'james@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Fulfilled, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => now()->subDays(25), 'lines' => [['handle' => 'striped-polo-shirt', 'variant' => 'XL', 'quantity' => 1]], 'subtotal_amount' => 2799, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 447, 'total_amount' => 3298, 'provider_payment_id' => 'mock_test_order1011', 'payment_status' => PaymentStatus::Captured, 'fulfillment' => ['status' => FulfillmentShipmentStatus::Delivered, 'tracking_company' => 'FedEx', 'tracking_number' => 'FX9998887776', 'shipped_at' => now()->subDays(23), 'delivered_at' => now()->subDays(20), 'line_indexes' => [0]]]),
            array_merge($base, ['order_number' => '#1012', 'customer' => 'lisa@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDays(4), 'lines' => [['handle' => 'chino-shorts', 'variant' => '34 / Navy', 'quantity' => 2]], 'subtotal_amount' => 7998, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1277, 'total_amount' => 8497, 'provider_payment_id' => 'mock_test_order1012', 'payment_status' => PaymentStatus::Captured]),
            array_merge($base, ['order_number' => '#1013', 'customer' => 'robert@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDay(), 'lines' => [['handle' => 'wide-leg-trousers', 'variant' => 'M', 'quantity' => 1], ['handle' => 'wool-scarf', 'variant' => 'Burgundy', 'quantity' => 1]], 'subtotal_amount' => 7998, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 1277, 'total_amount' => 8497, 'provider_payment_id' => 'mock_test_order1013', 'payment_status' => PaymentStatus::Captured]),
            array_merge($base, ['order_number' => '#1014', 'customer' => 'anna@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => now()->subDays(14), 'lines' => [['handle' => 'gift-card', 'variant' => '50 EUR', 'quantity' => 1]], 'subtotal_amount' => 5000, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 798, 'total_amount' => 5000, 'provider_payment_id' => 'mock_test_order1014', 'payment_status' => PaymentStatus::Captured, 'fulfillment' => ['status' => FulfillmentShipmentStatus::Delivered, 'tracking_company' => null, 'tracking_number' => null, 'shipped_at' => now()->subDays(14), 'delivered_at' => now()->subDays(14), 'line_indexes' => [0]]]),
            array_merge($base, ['order_number' => '#1015', 'customer' => 'customer@acme.test', 'payment_method' => PaymentMethod::BankTransfer, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now(), 'lines' => [['handle' => 'classic-cotton-t-shirt', 'variant' => 'M / White', 'quantity' => 1, 'discount_amount' => 250, 'discount_id' => $discountId], ['handle' => 'graphic-print-tee', 'variant' => 'M', 'quantity' => 1, 'discount_amount' => 300, 'discount_id' => $discountId]], 'subtotal_amount' => 5498, 'discount_amount' => 550, 'shipping_amount' => 499, 'tax_amount' => 790, 'total_amount' => 5447, 'provider_payment_id' => 'mock_test_order1015', 'payment_status' => PaymentStatus::Captured]),
        ];
    }

    private function electronicsOrders(): array
    {
        $storeId = Store::query()->where('handle', 'acme-electronics')->value('id');

        return [
            ['store_id' => $storeId, 'order_number' => '#5001', 'customer' => 'techfan@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Fulfilled, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Fulfilled, 'placed_at' => now()->subDays(6), 'lines' => [['handle' => 'pro-laptop-15', 'variant' => '512GB', 'quantity' => 1], ['handle' => 'usb-c-cable-2m', 'variant' => 'Default', 'quantity' => 1]], 'subtotal_amount' => 121298, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => 121298, 'provider_payment_id' => 'mock_test_order5001', 'payment_status' => PaymentStatus::Captured],
            ['store_id' => $storeId, 'order_number' => '#5002', 'customer' => 'gadgetlover@example.com', 'payment_method' => PaymentMethod::CreditCard, 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subDays(2), 'lines' => [['handle' => 'wireless-headphones', 'variant' => 'Black', 'quantity' => 1]], 'subtotal_amount' => 14999, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => 14999, 'provider_payment_id' => 'mock_test_order5002', 'payment_status' => PaymentStatus::Captured],
            ['store_id' => $storeId, 'order_number' => '#5003', 'customer' => 'techfan@example.com', 'payment_method' => PaymentMethod::BankTransfer, 'status' => OrderStatus::Pending, 'financial_status' => FinancialStatus::Pending, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'placed_at' => now()->subHours(4), 'lines' => [['handle' => 'monitor-stand', 'variant' => 'Default', 'quantity' => 1]], 'subtotal_amount' => 4999, 'discount_amount' => 0, 'shipping_amount' => 0, 'tax_amount' => 0, 'total_amount' => 4999, 'provider_payment_id' => 'mock_test_order5003', 'payment_status' => PaymentStatus::Pending],
        ];
    }
}
