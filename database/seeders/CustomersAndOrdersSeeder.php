<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomersAndOrdersSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $customerData = [
            ['email' => 'alice@shop.test', 'name' => 'Alice Example'],
            ['email' => 'bob@shop.test', 'name' => 'Bob Example'],
            ['email' => 'carol@shop.test', 'name' => 'Carol Example'],
            ['email' => 'dan@shop.test', 'name' => 'Dan Example'],
            ['email' => 'eve@shop.test', 'name' => 'Eve Example'],
        ];

        $customers = [];

        foreach ($customerData as $data) {
            $customers[] = Customer::query()->firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password_hash' => Hash::make('password'),
                    'marketing_opt_in' => false,
                ]
            );
        }

        $products = Product::query()->where('store_id', $store->id)->with('variants')->take(3)->get();

        if ($products->isEmpty()) {
            return;
        }

        $orderStates = [
            ['number' => 'D-1001', 'status' => 'pending', 'financial' => 'pending', 'fulfillment' => 'unfulfilled'],
            ['number' => 'D-1002', 'status' => 'fulfilled', 'financial' => 'paid', 'fulfillment' => 'fulfilled'],
            ['number' => 'D-1003', 'status' => 'refunded', 'financial' => 'refunded', 'fulfillment' => 'fulfilled'],
        ];

        foreach ($orderStates as $index => $state) {
            $existing = Order::query()
                ->where('store_id', $store->id)
                ->where('order_number', $state['number'])
                ->first();

            if ($existing !== null) {
                continue;
            }

            $customer = $customers[$index];
            $product = $products[$index % $products->count()];
            $variant = $product->variants->first();

            if ($variant === null) {
                continue;
            }

            $unitPrice = (int) $variant->price_amount;
            $quantity = 1;
            $lineTotal = $unitPrice * $quantity;
            $shipping = 599;
            $total = $lineTotal + $shipping;

            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'order_number' => $state['number'],
                'payment_method' => 'credit_card',
                'status' => $state['status'],
                'financial_status' => $state['financial'],
                'fulfillment_status' => $state['fulfillment'],
                'currency' => 'EUR',
                'subtotal_amount' => $lineTotal,
                'discount_amount' => 0,
                'shipping_amount' => $shipping,
                'tax_amount' => 0,
                'total_amount' => $total,
                'email' => $customer->email,
                'placed_at' => now()->subDays($index + 1),
            ]);

            OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $quantity,
                'unit_price_amount' => $unitPrice,
                'total_amount' => $lineTotal,
            ]);
        }
    }
}
