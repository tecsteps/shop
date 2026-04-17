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
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $customers = Customer::query()->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get();

        if ($customers->isEmpty()) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereIn('product_id', DB::table('products')->where('store_id', $store->getKey())->pluck('id'))
            ->get();

        if ($variants->isEmpty()) {
            return;
        }

        $scenarios = [
            ['status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled, 'method' => PaymentMethod::CreditCard, 'refund' => null, 'shipment' => null],
            ['status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Partial, 'method' => PaymentMethod::CreditCard, 'refund' => null, 'shipment' => FulfillmentShipmentStatus::Pending, 'partial' => true],
            ['status' => OrderStatus::Fulfilled, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled, 'method' => PaymentMethod::CreditCard, 'refund' => null, 'shipment' => FulfillmentShipmentStatus::Delivered],
            ['status' => OrderStatus::Fulfilled, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled, 'method' => PaymentMethod::Paypal, 'refund' => null, 'shipment' => FulfillmentShipmentStatus::Shipped],
            ['status' => OrderStatus::Pending, 'financial' => FinancialStatus::Pending, 'fulfillment' => FulfillmentStatus::Unfulfilled, 'method' => PaymentMethod::BankTransfer, 'refund' => null, 'shipment' => null],
            ['status' => OrderStatus::Cancelled, 'financial' => FinancialStatus::Voided, 'fulfillment' => FulfillmentStatus::Unfulfilled, 'method' => PaymentMethod::CreditCard, 'refund' => null, 'shipment' => null],
            ['status' => OrderStatus::Refunded, 'financial' => FinancialStatus::Refunded, 'fulfillment' => FulfillmentStatus::Fulfilled, 'method' => PaymentMethod::CreditCard, 'refund' => 'full', 'shipment' => FulfillmentShipmentStatus::Delivered],
            ['status' => OrderStatus::Paid, 'financial' => FinancialStatus::PartiallyRefunded, 'fulfillment' => FulfillmentStatus::Fulfilled, 'method' => PaymentMethod::CreditCard, 'refund' => 'partial', 'shipment' => FulfillmentShipmentStatus::Delivered],
            ['status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Unfulfilled, 'method' => PaymentMethod::Paypal, 'refund' => null, 'shipment' => null],
            ['status' => OrderStatus::Fulfilled, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Fulfilled, 'method' => PaymentMethod::CreditCard, 'refund' => null, 'shipment' => FulfillmentShipmentStatus::Shipped],
            ['status' => OrderStatus::Paid, 'financial' => FinancialStatus::Paid, 'fulfillment' => FulfillmentStatus::Partial, 'method' => PaymentMethod::CreditCard, 'refund' => null, 'shipment' => FulfillmentShipmentStatus::Shipped, 'partial' => true],
            ['status' => OrderStatus::Pending, 'financial' => FinancialStatus::Pending, 'fulfillment' => FulfillmentStatus::Unfulfilled, 'method' => PaymentMethod::BankTransfer, 'refund' => null, 'shipment' => null],
        ];

        $orderNumber = 1001;

        foreach ($scenarios as $index => $scenario) {
            $customer = $customers[$index % $customers->count()];
            $variantA = $variants->random();
            $variantB = $variants->random();
            $placedAt = Carbon::now()->subDays(random_int(0, 12))->subHours(random_int(0, 20));

            $lineQty1 = random_int(1, 3);
            $lineQty2 = random_int(1, 2);

            $subtotal = $variantA->price_amount * $lineQty1 + $variantB->price_amount * $lineQty2;
            $shipping = 799;
            $tax = (int) floor($subtotal * 0.08);
            $total = $subtotal + $shipping + $tax;

            $address = [
                'first_name' => explode(' ', $customer->name)[0],
                'last_name' => explode(' ', $customer->name)[1] ?? '',
                'address1' => random_int(100, 999).' Main St',
                'city' => 'Austin',
                'province_code' => 'TX',
                'country_code' => 'US',
                'postal_code' => (string) random_int(10_000, 99_999),
            ];

            $order = Order::query()->create([
                'store_id' => $store->getKey(),
                'customer_id' => $customer->getKey(),
                'order_number' => '#'.($orderNumber + $index),
                'payment_method' => $scenario['method']->value,
                'status' => $scenario['status']->value,
                'financial_status' => $scenario['financial']->value,
                'fulfillment_status' => $scenario['fulfillment']->value,
                'currency' => 'USD',
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'shipping_amount' => $shipping,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'email' => $customer->email,
                'billing_address_json' => $address,
                'shipping_address_json' => $address,
                'placed_at' => $placedAt,
            ]);

            $line1 = OrderLine::query()->create([
                'order_id' => $order->getKey(),
                'product_id' => $variantA->product_id,
                'variant_id' => $variantA->getKey(),
                'title_snapshot' => $variantA->product->title ?? 'Product',
                'sku_snapshot' => $variantA->sku,
                'quantity' => $lineQty1,
                'unit_price_amount' => $variantA->price_amount,
                'total_amount' => $variantA->price_amount * $lineQty1,
                'tax_lines_json' => [],
                'discount_allocations_json' => [],
            ]);

            $line2 = OrderLine::query()->create([
                'order_id' => $order->getKey(),
                'product_id' => $variantB->product_id,
                'variant_id' => $variantB->getKey(),
                'title_snapshot' => $variantB->product->title ?? 'Product',
                'sku_snapshot' => $variantB->sku,
                'quantity' => $lineQty2,
                'unit_price_amount' => $variantB->price_amount,
                'total_amount' => $variantB->price_amount * $lineQty2,
                'tax_lines_json' => [],
                'discount_allocations_json' => [],
            ]);

            if ($scenario['financial'] === FinancialStatus::Paid || $scenario['financial'] === FinancialStatus::PartiallyRefunded || $scenario['financial'] === FinancialStatus::Refunded) {
                Payment::query()->create([
                    'order_id' => $order->getKey(),
                    'provider' => 'mock',
                    'method' => $scenario['method']->value,
                    'provider_payment_id' => 'mock_'.uniqid(),
                    'status' => PaymentStatus::Captured->value,
                    'amount' => $total,
                    'currency' => 'USD',
                    'raw_json_encrypted' => ['result' => 'captured'],
                    'created_at' => $placedAt,
                ]);
            } elseif ($scenario['financial'] === FinancialStatus::Voided) {
                Payment::query()->create([
                    'order_id' => $order->getKey(),
                    'provider' => 'mock',
                    'method' => $scenario['method']->value,
                    'provider_payment_id' => 'mock_'.uniqid(),
                    'status' => PaymentStatus::Failed->value,
                    'amount' => $total,
                    'currency' => 'USD',
                    'raw_json_encrypted' => ['result' => 'voided'],
                    'created_at' => $placedAt,
                ]);
            } elseif ($scenario['financial'] === FinancialStatus::Pending) {
                Payment::query()->create([
                    'order_id' => $order->getKey(),
                    'provider' => 'mock',
                    'method' => $scenario['method']->value,
                    'provider_payment_id' => 'mock_'.uniqid(),
                    'status' => PaymentStatus::Pending->value,
                    'amount' => $total,
                    'currency' => 'USD',
                    'raw_json_encrypted' => ['result' => 'pending'],
                    'created_at' => $placedAt,
                ]);
            }

            if ($scenario['shipment'] !== null) {
                $fulfillment = Fulfillment::query()->create([
                    'order_id' => $order->getKey(),
                    'status' => $scenario['shipment']->value,
                    'tracking_company' => 'UPS',
                    'tracking_number' => 'TRK'.random_int(1_000_000, 9_999_999),
                    'tracking_url' => 'https://example.com/track/TRK'.random_int(100_000, 999_999),
                    'shipped_at' => $scenario['shipment'] === FulfillmentShipmentStatus::Pending ? null : $placedAt->copy()->addHours(2),
                    'created_at' => $placedAt,
                ]);

                $partial = ! empty($scenario['partial']);

                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->getKey(),
                    'order_line_id' => $line1->getKey(),
                    'quantity' => $line1->quantity,
                ]);

                if (! $partial) {
                    FulfillmentLine::query()->create([
                        'fulfillment_id' => $fulfillment->getKey(),
                        'order_line_id' => $line2->getKey(),
                        'quantity' => $line2->quantity,
                    ]);
                }
            }

            if ($scenario['refund'] === 'full') {
                Refund::query()->create([
                    'order_id' => $order->getKey(),
                    'payment_id' => $order->payments()->first()?->getKey(),
                    'amount' => $total,
                    'reason' => 'Customer requested return.',
                    'status' => RefundStatus::Processed->value,
                    'provider_refund_id' => 'refund_'.uniqid(),
                    'created_at' => $placedAt->copy()->addDay(),
                ]);
            } elseif ($scenario['refund'] === 'partial') {
                Refund::query()->create([
                    'order_id' => $order->getKey(),
                    'payment_id' => $order->payments()->first()?->getKey(),
                    'amount' => (int) floor($total / 3),
                    'reason' => 'One item returned.',
                    'status' => RefundStatus::Processed->value,
                    'provider_refund_id' => 'refund_'.uniqid(),
                    'created_at' => $placedAt->copy()->addDay(),
                ]);
            }
        }
    }
}
