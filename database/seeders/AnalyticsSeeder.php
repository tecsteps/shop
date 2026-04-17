<?php

namespace Database\Seeders;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        app()->instance('current_store', $store);

        $this->seedDailyAnalytics($store);
        $this->seedAnalyticsEvents($store);
    }

    private function seedDailyAnalytics(Store $store): void
    {
        for ($i = 30; $i >= 0; $i--) {
            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(rand(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * rand(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * rand(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * rand(35, 55) / 100));
            $aov = rand(4000, 9000);
            $revenue = $orders * $aov;

            AnalyticsDaily::firstOrCreate(
                ['store_id' => $store->id, 'date' => now()->subDays($i)->format('Y-m-d')],
                [
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'orders_count' => $orders,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $aov,
                ]
            );
        }
    }

    private function seedAnalyticsEvents(Store $store): void
    {
        if (AnalyticsEvent::where('store_id', $store->id)->exists()) {
            return;
        }

        $customers = Customer::where('store_id', $store->id)->pluck('id')->toArray();
        $products = Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->with('variants')
            ->get();

        $pages = ['/', '/collections/new-arrivals', '/collections/t-shirts', '/collections/pants-jeans', '/collections/sale'];
        $referrers = ['https://www.google.com', 'https://www.facebook.com', 'https://www.instagram.com', null, null, null, null];
        $searchQueries = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'sneakers', 'belt', 'scarf'];

        $eventTypes = [
            'page_view' => 88,
            'product_view' => 55,
            'add_to_cart' => 33,
            'checkout_started' => 22,
            'checkout_completed' => 11,
            'search' => 11,
        ];

        $sessions = [];
        for ($s = 0; $s < 35; $s++) {
            $sessions[] = (string) Str::uuid();
        }

        foreach ($eventTypes as $type => $count) {
            for ($e = 0; $e < $count; $e++) {
                $sessionId = $sessions[array_rand($sessions)];
                $customerId = (rand(1, 100) <= 30) ? $customers[array_rand($customers)] : null;
                $daysAgo = rand(0, 6);
                $hoursAgo = rand(0, 23);
                $createdAt = now()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes(rand(0, 59));

                $properties = match ($type) {
                    'page_view' => [
                        'url' => $pages[array_rand($pages)],
                        'referrer' => $referrers[array_rand($referrers)],
                    ],
                    'product_view' => $this->productViewProperties($products),
                    'add_to_cart' => $this->addToCartProperties($products),
                    'checkout_started' => [
                        'cart_id' => rand(1, 500),
                        'item_count' => rand(1, 4),
                        'cart_total' => rand(2000, 15000),
                    ],
                    'checkout_completed' => [
                        'order_id' => rand(1, 100),
                        'order_number' => '#'.rand(1001, 1099),
                        'total_amount' => rand(3000, 20000),
                    ],
                    'search' => [
                        'query' => $searchQueries[array_rand($searchQueries)],
                        'results_count' => rand(0, 15),
                    ],
                    default => [],
                };

                AnalyticsEvent::create([
                    'store_id' => $store->id,
                    'type' => $type,
                    'session_id' => $sessionId,
                    'customer_id' => $customerId,
                    'properties_json' => $properties,
                    'occurred_at' => $createdAt,
                    'created_at' => $createdAt,
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function productViewProperties($products): array
    {
        $product = $products->random();

        return [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'url' => '/products/'.$product->handle,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function addToCartProperties($products): array
    {
        $product = $products->random();
        $variant = $product->variants->first();

        return [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => rand(1, 3),
            'price_amount' => $variant?->price_amount ?? 0,
        ];
    }
}
