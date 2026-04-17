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
        $fashion = Store::where('handle', 'acme-fashion')->first();

        app()->instance('current_store', $fashion);

        $this->seedDailyAnalytics($fashion);
        $this->seedAnalyticsEvents($fashion);
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

            AnalyticsDaily::create([
                'store_id' => $store->id,
                'date' => now()->subDays($i)->format('Y-m-d'),
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCart,
                'checkout_started_count' => $checkoutStarted,
                'checkout_completed_count' => $orders,
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $aov,
            ]);
        }
    }

    private function seedAnalyticsEvents(Store $store): void
    {
        $customers = Customer::where('store_id', $store->id)->pluck('id')->toArray();
        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->get();

        // Create ~30-40 sessions
        $sessionCount = rand(30, 40);
        $sessions = [];
        for ($s = 0; $s < $sessionCount; $s++) {
            $sessions[] = Str::uuid()->toString();
        }

        $eventTypes = [
            'page_view' => 40,
            'product_view' => 25,
            'add_to_cart' => 15,
            'checkout_started' => 10,
            'checkout_completed' => 5,
            'search' => 5,
        ];

        $totalEvents = 220;
        $searchQueries = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'shoes', 'sneakers', 'belt', 'scarf'];
        $urls = ['/', '/collections/t-shirts', '/collections/new-arrivals', '/collections/sale', '/products/classic-cotton-t-shirt', '/search'];

        foreach ($eventTypes as $type => $pct) {
            $count = (int) round($totalEvents * $pct / 100);

            for ($e = 0; $e < $count; $e++) {
                $sessionId = $sessions[array_rand($sessions)];
                $customerId = rand(1, 100) <= 30 ? $customers[array_rand($customers)] : null;
                $createdAt = now()->subDays(rand(0, 6))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                $properties = match ($type) {
                    'page_view' => [
                        'url' => $urls[array_rand($urls)],
                        'referrer' => rand(1, 100) <= 40 ? 'https://www.google.com' : null,
                    ],
                    'product_view' => [
                        'product_id' => $products->random()->id,
                        'product_title' => $products->random()->title,
                        'url' => '/products/'.$products->random()->handle,
                    ],
                    'add_to_cart' => [
                        'product_id' => $products->random()->id,
                        'variant_id' => $products->random()->id,
                        'quantity' => rand(1, 3),
                        'price_amount' => rand(1999, 11999),
                    ],
                    'checkout_started' => [
                        'cart_id' => rand(1, 100),
                        'item_count' => rand(1, 4),
                        'cart_total' => rand(2499, 50000),
                    ],
                    'checkout_completed' => [
                        'order_id' => rand(1, 15),
                        'order_number' => '#'.rand(1001, 1015),
                        'total_amount' => rand(2998, 50498),
                    ],
                    'search' => [
                        'query' => $searchQueries[array_rand($searchQueries)],
                        'results_count' => rand(0, 20),
                    ],
                };

                AnalyticsEvent::create([
                    'store_id' => $store->id,
                    'type' => $type,
                    'session_id' => $sessionId,
                    'customer_id' => $customerId,
                    'properties_json' => $properties,
                    'client_event_id' => Str::uuid()->toString(),
                    'occurred_at' => $createdAt->toIso8601String(),
                    'created_at' => $createdAt->toIso8601String(),
                ]);
            }
        }
    }
}
