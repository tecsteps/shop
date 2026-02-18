<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();
        if (! $store) {
            return;
        }

        $customer = Customer::query()->withoutGlobalScopes()->where('store_id', $store->id)->first();
        if (! $customer) {
            $customer = Customer::factory()->create([
                'store_id' => $store->id,
                'name' => 'Jane Doe',
                'email' => 'customer@acme.com',
            ]);
        }

        $products = Product::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('variants')
            ->limit(3)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        // Order 1: Paid credit card order
        $this->createSampleOrder(
            $store,
            $customer,
            $products,
            '#1001',
            PaymentMethod::CreditCard,
            OrderStatus::Paid,
            FinancialStatus::Paid,
            PaymentStatus::Captured,
        );

        // Order 2: Fulfilled order
        $order2 = $this->createSampleOrder(
            $store,
            $customer,
            $products->take(1),
            '#1002',
            PaymentMethod::Paypal,
            OrderStatus::Fulfilled,
            FinancialStatus::Paid,
            PaymentStatus::Captured,
            FulfillmentStatus::Fulfilled,
        );
        $this->createFulfillment($order2);

        // Order 3: Pending bank transfer
        $this->createSampleOrder(
            $store,
            $customer,
            $products->take(2),
            '#1003',
            PaymentMethod::BankTransfer,
            OrderStatus::Pending,
            FinancialStatus::Pending,
            PaymentStatus::Pending,
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     */
    private function createSampleOrder(
        Store $store,
        Customer $customer,
        $products,
        string $orderNumber,
        PaymentMethod $paymentMethod,
        OrderStatus $orderStatus,
        FinancialStatus $financialStatus,
        PaymentStatus $paymentStatus,
        FulfillmentStatus $fulfillmentStatus = FulfillmentStatus::Unfulfilled,
    ): Order {
        $subtotal = 0;
        $lineData = [];

        foreach ($products as $product) {
            $variant = $product->variants->first();
            if (! $variant) {
                continue;
            }

            $quantity = rand(1, 3);
            $lineTotal = $variant->price_amount * $quantity;
            $subtotal += $lineTotal;

            $lineData[] = [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $quantity,
                'unit_price_amount' => $variant->price_amount,
                'total_amount' => $lineTotal,
            ];
        }

        $tax = (int) round($subtotal * 0.19);
        $shipping = 799;
        $total = $subtotal + $tax + $shipping;

        /** @var Order $order */
        $order = Order::query()->withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'payment_method' => $paymentMethod,
            'status' => $orderStatus,
            'financial_status' => $financialStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'currency' => $store->default_currency,
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'email' => $customer->email,
            'shipping_address_json' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'Berlin',
                'country_code' => 'DE',
                'zip' => '10115',
            ],
            'billing_address_json' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'Berlin',
                'country_code' => 'DE',
                'zip' => '10115',
            ],
            'placed_at' => now()->subDays(rand(0, 14)),
        ]);

        foreach ($lineData as $data) {
            OrderLine::query()->create(array_merge($data, [
                'order_id' => $order->id,
                'tax_lines_json' => [],
                'discount_allocations_json' => [],
            ]));
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $paymentMethod,
            'provider_payment_id' => 'mock_'.Str::random(16),
            'status' => $paymentStatus,
            'amount' => $total,
            'currency' => $store->default_currency,
            'created_at' => $order->placed_at,
        ]);

        return $order;
    }

    private function createFulfillment(Order $order): void
    {
        $order->loadMissing('lines');

        /** @var Fulfillment $fulfillment */
        $fulfillment = Fulfillment::query()->create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'tracking_company' => 'DHL',
            'tracking_number' => '1234567890',
            'shipped_at' => now()->subDays(2),
            'created_at' => now()->subDays(3),
        ]);

        foreach ($order->lines as $line) {
            FulfillmentLine::query()->create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }
    }
}
