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
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedFashionOrders();
            $this->seedElectronicsOrders();
        });
    }

    private function seedFashionOrders(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $orders = [
            ['#1001', 'customer@acme.test', 'credit_card', 'paid', 'paid', 'unfulfilled', 4998, 0, 499, 798, 5497, now()->subDays(2), [['classic-cotton-t-shirt', 0, 2]], null],
            ['#1002', 'customer@acme.test', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 8498, 0, 499, 1357, 8997, now()->subDays(10), [['organic-hoodie', 1, 1], ['classic-cotton-t-shirt', 7, 1]], ['delivered', 'DHL', 'DHL1234567890', 8, 'all']],
            ['#1003', 'jane@example.com', 'credit_card', 'paid', 'paid', 'partial', 11498, 0, 499, 1836, 11997, now()->subDays(5), [['premium-slim-fit-jeans', 4, 1], ['leather-belt', 2, 1]], ['shipped', 'DHL', 'DHL9876543210', 3, [0]]],
            ['#1004', 'customer@acme.test', 'credit_card', 'cancelled', 'refunded', 'unfulfilled', 2499, 0, 499, 399, 2998, now()->subDays(15), [['classic-cotton-t-shirt', 5, 1]], null],
            ['#1005', 'jane@example.com', 'bank_transfer', 'pending', 'pending', 'unfulfilled', 3499, 0, 499, 559, 3998, now()->subHours(2), [['leather-belt', 1, 1]], null],
            ['#1006', 'michael@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 11999, 0, 499, 1916, 12498, now()->subDay(), [['running-sneakers', 9, 1]], null],
            ['#1007', 'sarah@example.com', 'paypal', 'fulfilled', 'paid', 'fulfilled', 9997, 0, 499, 1596, 10496, now()->subDays(20), [['v-neck-linen-tee', 3, 2], ['wool-scarf', 0, 1]], ['delivered', 'DHL', 'DHL1112223334', 18, 'all']],
            ['#1008', 'david@example.com', 'credit_card', 'paid', 'partially_refunded', 'fulfilled', 8498, 0, 499, 1357, 8997, now()->subDays(12), [['cargo-pants', 3, 1], ['graphic-print-tee', 2, 1]], ['delivered', 'UPS', 'UPS5556667778', 10, 'all']],
            ['#1009', 'emma@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 4498, 0, 499, 718, 4997, now()->subDays(3), [['canvas-tote-bag', 0, 1], ['bucket-hat', 1, 1]], null],
            ['#1010', 'customer@acme.test', 'paypal', 'paid', 'paid', 'unfulfilled', 49999, 0, 499, 7983, 50498, now()->subDay(), [['cashmere-overcoat', 2, 1]], null],
            ['#1011', 'james@example.com', 'credit_card', 'paid', 'paid', 'fulfilled', 2799, 0, 499, 447, 3298, now()->subDays(25), [['striped-polo-shirt', 3, 1]], ['delivered', 'FedEx', 'FX9998887776', 23, 'all']],
            ['#1012', 'lisa@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 7998, 0, 499, 1277, 8497, now()->subDays(4), [['chino-shorts', 4, 2]], null],
            ['#1013', 'robert@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 7998, 0, 499, 1277, 8497, now()->subDay(), [['wide-leg-trousers', 1, 1], ['wool-scarf', 1, 1]], null],
            ['#1014', 'anna@example.com', 'credit_card', 'paid', 'paid', 'fulfilled', 5000, 0, 0, 798, 5000, now()->subDays(14), [['gift-card', 1, 1]], ['delivered', null, null, 14, 'all']],
            ['#1015', 'customer@acme.test', 'bank_transfer', 'paid', 'paid', 'unfulfilled', 5498, 550, 499, 790, 5447, now(), [['classic-cotton-t-shirt', 3, 1, 250], ['graphic-print-tee', 1, 1, 300]], null],
        ];

        foreach ($orders as $data) {
            $this->seedOrder($store, $data);
        }

        $this->seedRefund($store, '#1004', 2998, 'Customer requested cancellation', 'mock_re_test_order1004');
        $this->seedRefund($store, '#1008', 2999, 'Item returned', 'mock_re_test_order1008');
    }

    private function seedElectronicsOrders(): void
    {
        $store = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        $orders = [
            ['#5001', 'techfan@example.com', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 121298, 0, 0, 19367, 121298, now()->subDays(7), [['pro-laptop-15', 1, 1], ['usb-c-cable-2m', 0, 1]], ['delivered', 'DHL', 'DHL5001000001', 5, 'all']],
            ['#5002', 'gadgetlover@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 14999, 0, 0, 2395, 14999, now()->subDays(2), [['wireless-headphones', 0, 1]], null],
            ['#5003', 'techfan@example.com', 'bank_transfer', 'pending', 'pending', 'unfulfilled', 4999, 0, 0, 798, 4999, now()->subHours(3), [['monitor-stand', 0, 1]], null],
        ];

        foreach ($orders as $data) {
            $this->seedOrder($store, $data);
        }
    }

    /**
     * @param  array<int, mixed>  $data
     */
    private function seedOrder(Store $store, array $data): void
    {
        [$number, $email, $method, $status, $financial, $fulfillmentStatus, $subtotal, $discount, $shipping, $tax, $total, $placedAt, $lines, $fulfillmentData] = $data;
        $customer = Customer::query()->where('store_id', $store->id)->where('email', $email)->firstOrFail();
        $address = $customer->addresses()->where('is_default', true)->firstOrFail()->address_json;

        $order = Order::query()->updateOrCreate(
            ['store_id' => $store->id, 'order_number' => $number],
            [
                'customer_id' => $customer->id,
                'payment_method' => $method,
                'status' => $status,
                'financial_status' => $financial,
                'fulfillment_status' => $fulfillmentStatus,
                'currency' => 'EUR',
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'shipping_amount' => $shipping,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'email' => $email,
                'billing_address_json' => $address,
                'shipping_address_json' => $address,
                'placed_at' => $placedAt,
            ],
        );

        $orderLines = [];
        foreach ($lines as $lineData) {
            [$handle, $variantPosition, $quantity, $allocatedDiscount] = array_pad($lineData, 4, 0);
            $product = Product::query()->where('store_id', $store->id)->where('handle', $handle)->firstOrFail();
            $variant = $product->variants()->where('position', $variantPosition)->firstOrFail();
            $allocations = [];

            if ($allocatedDiscount > 0) {
                $discountModel = Discount::query()->where('store_id', $store->id)->where('code', 'WELCOME10')->firstOrFail();
                $allocations[] = ['discount_id' => $discountModel->id, 'amount' => $allocatedDiscount];
            }

            $orderLines[] = OrderLine::query()->updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id, 'variant_id' => $variant->id],
                [
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $quantity,
                    'unit_price_amount' => $variant->price_amount,
                    'total_amount' => $variant->price_amount * $quantity,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => $allocations,
                ],
            );
        }

        $paymentStatus = $financial === 'pending' ? 'pending' : ($financial === 'refunded' ? 'refunded' : 'captured');
        Payment::query()->updateOrCreate(
            ['provider_payment_id' => 'mock_test_order'.mb_substr($number, 1)],
            [
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $method,
                'status' => $paymentStatus,
                'amount' => $total,
                'currency' => 'EUR',
                'raw_json_encrypted' => null,
            ],
        );

        if ($fulfillmentData !== null) {
            [$shipmentStatus, $company, $trackingNumber, $daysAgo, $fulfilledIndexes] = $fulfillmentData;
            $fulfillment = Fulfillment::query()->updateOrCreate(
                ['order_id' => $order->id, 'tracking_number' => $trackingNumber],
                [
                    'status' => $shipmentStatus,
                    'tracking_company' => $company,
                    'tracking_url' => $trackingNumber === null ? null : 'https://tracking.example.com/'.$trackingNumber,
                    'shipped_at' => now()->subDays($daysAgo),
                ],
            );

            $indexes = $fulfilledIndexes === 'all' ? array_keys($orderLines) : $fulfilledIndexes;
            foreach ($indexes as $index) {
                FulfillmentLine::query()->updateOrCreate(
                    ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $orderLines[$index]->id],
                    ['quantity' => $orderLines[$index]->quantity],
                );
            }
        }
    }

    private function seedRefund(Store $store, string $orderNumber, int $amount, string $reason, string $providerRefundId): void
    {
        $order = Order::query()->where('store_id', $store->id)->where('order_number', $orderNumber)->firstOrFail();
        $payment = $order->payments()->firstOrFail();

        Refund::query()->updateOrCreate(
            ['provider_refund_id' => $providerRefundId],
            [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'processed',
            ],
        );
    }
}
