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
}
