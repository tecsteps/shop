<?php

namespace Database\Seeders;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $this->seedDaily($store);
        $this->seedEvents($store);
    }

    protected function seedDaily(Store $store): void
    {
        for ($i = 30; $i >= 0; $i--) {
            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(random_int(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * random_int(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * random_int(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * random_int(35, 55) / 100));
            $aov = random_int(4000, 9000);

            AnalyticsDaily::query()->updateOrCreate(
                ['store_id' => $store->getKey(), 'date' => today()->subDays($i)->toDateString()],
                [
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $orders,
                    'orders_count' => $orders,
                    'revenue_amount' => $orders * $aov,
                    'aov_amount' => $aov,
                ]
            );
        }
    }

    protected function seedEvents(Store $store): void
    {
        if (AnalyticsEvent::query()->where('store_id', $store->getKey())->count() >= 200) {
            return;
        }

        $products = Product::query()->where('store_id', $store->getKey())->get();
        $variants = ProductVariant::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->get();
        $orders = Order::query()->where('store_id', $store->getKey())->get();
        $customerIds = Customer::query()->where('store_id', $store->getKey())->pluck('id')->all();

        $distribution = [
            'page_view' => 88,
            'product_view' => 55,
            'add_to_cart' => 33,
            'checkout_started' => 22,
            'checkout_completed' => 11,
            'search' => 11,
        ];

        $types = [];

        foreach ($distribution as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $types[] = $type;
            }
        }

        shuffle($types);

        $sessions = [];

        for ($i = 0; $i < 36; $i++) {
            $sessions[] = (string) Str::uuid();
        }

        foreach ($types as $type) {
            $occurredAt = fake()->dateTimeBetween('-7 days', 'now');

            AnalyticsEvent::query()->create([
                'store_id' => $store->getKey(),
                'type' => $type,
                'session_id' => $sessions[array_rand($sessions)],
                'customer_id' => random_int(1, 100) <= 30 ? $customerIds[array_rand($customerIds)] : null,
                'properties_json' => $this->propertiesFor($type, $products, $variants, $orders),
                'client_event_id' => (string) Str::uuid(),
                'occurred_at' => $occurredAt,
                'created_at' => $occurredAt,
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    protected function propertiesFor(string $type, $products, $variants, $orders): array
    {
        $product = $products->isNotEmpty() ? $products->random() : null;

        return match ($type) {
            'page_view' => [
                'url' => fake()->randomElement(['/', '/collections/t-shirts', '/collections/new-arrivals', '/collections/sale']),
                'referrer' => fake()->boolean(40) ? 'https://www.google.com' : null,
            ],
            'product_view' => [
                'product_id' => $product?->getKey(),
                'product_title' => $product?->title,
                'url' => $product ? '/products/'.$product->handle : '/',
            ],
            'add_to_cart' => [
                'product_id' => $product?->getKey(),
                'variant_id' => ($variant = $variants->isNotEmpty() ? $variants->random() : null)?->getKey(),
                'quantity' => random_int(1, 3),
                'price_amount' => $variant?->price_amount ?? 2499,
            ],
            'checkout_started' => [
                'cart_id' => random_int(1, 500),
                'item_count' => random_int(1, 4),
                'cart_total' => random_int(2499, 19999),
            ],
            'checkout_completed' => ($order = $orders->isNotEmpty() ? $orders->random() : null) !== null
                ? ['order_id' => $order->getKey(), 'order_number' => $order->order_number, 'total_amount' => $order->total_amount]
                : ['order_id' => null, 'order_number' => null, 'total_amount' => 0],
            'search' => [
                'query' => fake()->randomElement(['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'sneakers']),
                'results_count' => random_int(0, 12),
            ],
            default => [],
        };
    }
}
