<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class AnalyticsEventSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $customers = Customer::withoutGlobalScopes()->where('store_id', $store->id)->pluck('id');
        $products = Product::withoutGlobalScopes()->where('store_id', $store->id)->with('variants')->get();
        $orders = Order::withoutGlobalScopes()->where('store_id', $store->id)->get();
        $types = [
            ...array_fill(0, 88, 'page_view'),
            ...array_fill(0, 55, 'product_view'),
            ...array_fill(0, 33, 'add_to_cart'),
            ...array_fill(0, 22, 'checkout_started'),
            ...array_fill(0, 11, 'checkout_completed'),
            ...array_fill(0, 11, 'search'),
        ];

        shuffle($types);

        foreach ($types as $index => $type) {
            $product = $products->random();
            $variant = $product->variants->first();
            $order = $orders->isNotEmpty() ? $orders->random() : null;

            AnalyticsEvent::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'client_event_id' => 'evt_seed_'.$index,
                ],
                [
                    'type' => $type,
                    'session_id' => 'sess_seed_'.fake()->numberBetween(1, 40),
                    'customer_id' => fake()->boolean(30) && $customers->isNotEmpty() ? $customers->random() : null,
                    'properties_json' => $this->properties($type, $product, $variant?->id, $order),
                    'occurred_at' => now()->subDays(fake()->numberBetween(0, 7))->subMinutes(fake()->numberBetween(0, 1440)),
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function properties(string $type, Product $product, ?int $variantId, ?Order $order): array
    {
        return match ($type) {
            'page_view' => ['url' => fake()->randomElement(['/', '/collections/summer-essentials', '/pages/about'])],
            'product_view' => ['product_id' => $product->id, 'product_title' => $product->title, 'url' => '/products/'.$product->handle],
            'add_to_cart' => ['product_id' => $product->id, 'variant_id' => $variantId, 'quantity' => 1, 'price_amount' => $product->variants->first()?->price_amount ?? 0],
            'checkout_started' => ['item_count' => fake()->numberBetween(1, 3), 'cart_total' => fake()->numberBetween(2500, 15000)],
            'checkout_completed' => ['order_id' => $order?->id, 'order_number' => $order?->order_number, 'total_amount' => $order?->total_amount ?? fake()->numberBetween(4000, 9000)],
            'search' => ['query' => fake()->randomElement(['linen', 'tee', 'summer', 'shirt']), 'results_count' => fake()->numberBetween(1, 6)],
            default => [],
        };
    }
}
