<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $variant = Product::query()
            ->where('store_id', $store->id)
            ->where('handle', 'linen-shirt')
            ->firstOrFail()
            ->variants()
            ->with('product')
            ->firstOrFail();

        $jane = Customer::query()
            ->where('store_id', $store->id)
            ->where('email', 'jane@example.com')
            ->firstOrFail();

        $john = Customer::query()
            ->where('store_id', $store->id)
            ->where('email', 'john@example.com')
            ->firstOrFail();

        $this->createPaidOrder($store, $jane, $variant);
        $this->createPendingBankTransferOrder($store, $john, $variant);
        $this->createFulfilledOrder($store, $jane, $variant);
        $this->createAdditionalFashionOrders($store);
        $this->createElectronicsOrders();
    }

    private function createPaidOrder(Store $store, Customer $customer, ProductVariant $variant): void
    {
        if (Order::withoutGlobalScopes()->where('store_id', $store->id)->where('order_number', '#1001')->exists()) {
            return;
        }

        $order = $this->createBaseOrder($store, $customer, $variant, '#1001', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, now()->subDays(3));
        $this->createPayment($order, PaymentMethod::CreditCard, PaymentStatus::Captured, 'mock_seed_1001');
        $variant->inventoryItem()->withoutGlobalScopes()->decrement('quantity_on_hand');
    }

    private function createPendingBankTransferOrder(Store $store, Customer $customer, ProductVariant $variant): void
    {
        if (Order::withoutGlobalScopes()->where('store_id', $store->id)->where('order_number', '#1002')->exists()) {
            return;
        }

        $order = $this->createBaseOrder($store, $customer, $variant, '#1002', PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, now()->subDay());
        $this->createPayment($order, PaymentMethod::BankTransfer, PaymentStatus::Pending, 'mock_seed_1002');
        $variant->inventoryItem()->withoutGlobalScopes()->increment('quantity_reserved');
    }

    private function createFulfilledOrder(Store $store, Customer $customer, ProductVariant $variant): void
    {
        if (Order::withoutGlobalScopes()->where('store_id', $store->id)->where('order_number', '#1003')->exists()) {
            return;
        }

        $order = $this->createBaseOrder($store, $customer, $variant, '#1003', PaymentMethod::Paypal, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, now()->subDays(7));
        $this->createPayment($order, PaymentMethod::Paypal, PaymentStatus::Captured, 'mock_seed_1003');

        $line = $order->lines()->firstOrFail();
        $fulfillment = $order->fulfillments()->create([
            'status' => FulfillmentShipmentStatus::Delivered,
            'tracking_company' => 'DHL',
            'tracking_number' => 'DHL1003',
            'tracking_url' => 'https://example.test/tracking/DHL1003',
            'shipped_at' => now()->subDays(6),
            'delivered_at' => now()->subDays(4),
        ]);

        $fulfillment->lines()->create([
            'order_line_id' => $line->id,
            'quantity' => 1,
        ]);

        $variant->inventoryItem()->withoutGlobalScopes()->decrement('quantity_on_hand');
    }

    private function createBaseOrder(
        Store $store,
        Customer $customer,
        ProductVariant $variant,
        string $orderNumber,
        PaymentMethod $method,
        OrderStatus $status,
        FinancialStatus $financialStatus,
        FulfillmentStatus $fulfillmentStatus,
        CarbonInterface $placedAt,
    ): Order {
        $address = $customer->addresses()->firstOrFail()->address_json;
        $order = Order::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'payment_method' => $method,
            'status' => $status,
            'financial_status' => $financialStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'currency' => $store->default_currency,
            'subtotal_amount' => 4999,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'tax_amount' => 1045,
            'total_amount' => 6544,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => $placedAt,
        ]);

        $order->lines()->create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'title_snapshot' => $variant->product->title,
            'sku_snapshot' => $variant->sku,
            'quantity' => 1,
            'unit_price_amount' => 4999,
            'total_amount' => 4999,
            'tax_lines_json' => [['title' => 'Tax', 'rate' => 1900, 'amount' => 950]],
            'discount_allocations_json' => [],
        ]);

        return $order;
    }

    private function createPayment(Order $order, PaymentMethod $method, PaymentStatus $status, string $reference): void
    {
        $order->payments()->create([
            'provider' => 'mock',
            'method' => $method,
            'provider_payment_id' => $reference,
            'status' => $status,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'raw_json_encrypted' => ['provider' => 'mock', 'reference' => $reference],
        ]);
    }

    private function createAdditionalFashionOrders(Store $store): void
    {
        $customers = Customer::query()
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->get();
        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id)->where('status', 'active'))
            ->with('product')
            ->orderBy('id')
            ->get();

        $rows = [
            ['#1004', 'customer@acme.test', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, 2],
            ['#1005', 'customer@acme.test', PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, PaymentStatus::Pending, 1],
            ['#1006', 'maria@example.com', PaymentMethod::Paypal, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, 4],
            ['#1007', 'sam@example.com', PaymentMethod::CreditCard, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, PaymentStatus::Captured, 5],
            ['#1008', 'li@example.com', PaymentMethod::CreditCard, OrderStatus::Refunded, FinancialStatus::Refunded, FulfillmentStatus::Unfulfilled, PaymentStatus::Refunded, 6],
            ['#1009', 'fatima@example.com', PaymentMethod::Paypal, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, 7],
            ['#1010', 'noah@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, 8],
            ['#1011', 'emma@example.com', PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, PaymentStatus::Pending, 9],
            ['#1012', 'olivia@example.com', PaymentMethod::Paypal, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, 10],
            ['#1013', 'jane@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::PartiallyRefunded, FulfillmentStatus::Partial, PaymentStatus::Captured, 11],
            ['#1014', 'john@example.com', PaymentMethod::CreditCard, OrderStatus::Cancelled, FinancialStatus::Voided, FulfillmentStatus::Unfulfilled, PaymentStatus::Failed, 12],
            ['#1015', 'customer@acme.test', PaymentMethod::Paypal, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, 13],
        ];

        foreach ($rows as [$orderNumber, $email, $method, $status, $financialStatus, $fulfillmentStatus, $paymentStatus, $variantIndex]) {
            $customer = $customers->firstWhere('email', $email) ?? $customers->first();
            $variant = $variants->get($variantIndex) ?? $variants->first();

            if ($customer instanceof Customer && $variant instanceof ProductVariant) {
                $this->createSeedOrder($store, $customer, $variant, $orderNumber, $method, $status, $financialStatus, $fulfillmentStatus, $paymentStatus);
            }
        }
    }

    private function createElectronicsOrders(): void
    {
        $store = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        $customers = Customer::query()->where('store_id', $store->id)->orderBy('id')->get();
        $variants = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
            ->with('product')
            ->orderBy('id')
            ->get();

        foreach ([
            ['#5001', 0, 0, PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured],
            ['#5002', 1, 1, PaymentMethod::Paypal, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, PaymentStatus::Captured],
            ['#5003', 0, 2, PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, PaymentStatus::Pending],
        ] as [$orderNumber, $customerIndex, $variantIndex, $method, $status, $financialStatus, $fulfillmentStatus, $paymentStatus]) {
            $customer = $customers->get($customerIndex) ?? $customers->first();
            $variant = $variants->get($variantIndex) ?? $variants->first();

            if ($customer instanceof Customer && $variant instanceof ProductVariant) {
                $this->createSeedOrder($store, $customer, $variant, $orderNumber, $method, $status, $financialStatus, $fulfillmentStatus, $paymentStatus);
            }
        }
    }

    private function createSeedOrder(
        Store $store,
        Customer $customer,
        ProductVariant $variant,
        string $orderNumber,
        PaymentMethod $method,
        OrderStatus $status,
        FinancialStatus $financialStatus,
        FulfillmentStatus $fulfillmentStatus,
        PaymentStatus $paymentStatus,
    ): void {
        if (Order::withoutGlobalScopes()->where('store_id', $store->id)->where('order_number', $orderNumber)->exists()) {
            return;
        }

        $order = $this->createBaseOrder($store, $customer, $variant, $orderNumber, $method, $status, $financialStatus, $fulfillmentStatus, now()->subDays(fake()->numberBetween(1, 20)));
        $this->createPayment($order, $method, $paymentStatus, 'mock_seed_'.trim($orderNumber, '#'));

        if ($financialStatus === FinancialStatus::Pending) {
            $variant->inventoryItem()->withoutGlobalScopes()->increment('quantity_reserved');
        } elseif ($financialStatus === FinancialStatus::Paid || $financialStatus === FinancialStatus::PartiallyRefunded || $financialStatus === FinancialStatus::Refunded) {
            $variant->inventoryItem()->withoutGlobalScopes()->decrement('quantity_on_hand');
        }

        if ($fulfillmentStatus === FulfillmentStatus::Fulfilled) {
            $line = $order->lines()->firstOrFail();
            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => 'DHL',
                'tracking_number' => 'DHL'.trim($orderNumber, '#'),
                'tracking_url' => 'https://example.test/tracking/DHL'.trim($orderNumber, '#'),
                'shipped_at' => now()->subDays(2),
                'delivered_at' => now()->subDay(),
            ]);

            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => 1,
            ]);
        }
    }
}
