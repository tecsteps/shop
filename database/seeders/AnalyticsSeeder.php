<?php

namespace Database\Seeders;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

            $this->seedDailyAnalytics($fashion);
            $this->seedAnalyticsEvents($fashion);
        });
    }

    private function seedDailyAnalytics(Store $store): void
    {
        $existingCount = AnalyticsDaily::where('store_id', $store->id)->count();
        if ($existingCount >= 31) {
            return;
        }

        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dayFactor = 1 + (30 - $i) * 0.03;

            $visits = (int) round(rand(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * rand(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * rand(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * rand(35, 55) / 100));
            $aov = rand(4000, 9000);
            $revenue = $orders * $aov;

            DB::table('analytics_daily')->insertOrIgnore([
                'store_id' => $store->id,
                'date' => $date,
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $aov,
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCart,
                'checkout_started_count' => $checkoutStarted,
                'checkout_completed_count' => $orders,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAnalyticsEvents(Store $store): void
    {
        $customerIds = Customer::where('store_id', $store->id)->pluck('id')->all();
        $products = Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->with('variants')
            ->get();

        $urls = [
            '/',
            '/collections/new-arrivals',
            '/collections/t-shirts',
            '/collections/pants-jeans',
            '/collections/sale',
            '/products/classic-cotton-t-shirt',
            '/products/premium-slim-fit-jeans',
            '/products/organic-hoodie',
            '/products/running-sneakers',
            '/products/gift-card',
        ];

        $searchQueries = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'sneakers', 'belt', 'polo', 'scarf'];
        $referrers = ['https://www.google.com', 'https://www.instagram.com', 'https://www.facebook.com', null];

        // Event type distribution: page_view 40%, product_view 25%, add_to_cart 15%, checkout_started 10%, checkout_completed 5%, search 5%
        $eventTypes = array_merge(
            array_fill(0, 88, 'page_view'),
            array_fill(0, 55, 'product_view'),
            array_fill(0, 33, 'add_to_cart'),
            array_fill(0, 22, 'checkout_started'),
            array_fill(0, 11, 'checkout_completed'),
            array_fill(0, 11, 'search'),
        );

        // Create 35 session IDs
        $sessions = [];
        for ($s = 0; $s < 35; $s++) {
            $sessions[] = Str::uuid()->toString();
        }

        shuffle($eventTypes);

        $totalEvents = count($eventTypes);
        $existingCount = AnalyticsEvent::where('store_id', $store->id)->count();
        if ($existingCount >= $totalEvents) {
            return;
        }

        foreach ($eventTypes as $idx => $eventType) {
            $sessionId = $sessions[$idx % count($sessions)];
            $customerId = rand(1, 100) <= 30 ? $customerIds[array_rand($customerIds)] : null;

            // More recent events are more likely
            $daysAgo = $this->weightedRandomDays();
            $createdAt = now()->subDays($daysAgo)->subMinutes(rand(0, 1439));

            $properties = match ($eventType) {
                'page_view' => [
                    'url' => $urls[array_rand($urls)],
                    'referrer' => rand(1, 100) <= 40 ? $referrers[array_rand($referrers)] : null,
                ],
                'product_view' => $this->productViewProperties($products, $urls),
                'add_to_cart' => $this->addToCartProperties($products),
                'checkout_started' => [
                    'cart_id' => rand(1, 100),
                    'item_count' => rand(1, 4),
                    'cart_total' => rand(2000, 60000),
                ],
                'checkout_completed' => [
                    'order_id' => rand(1, 20),
                    'order_number' => '#'.rand(1001, 1015),
                    'total_amount' => rand(2000, 60000),
                ],
                'search' => [
                    'query' => $searchQueries[array_rand($searchQueries)],
                    'results_count' => rand(0, 15),
                ],
                default => [],
            };

            AnalyticsEvent::create([
                'store_id' => $store->id,
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'event_type' => $eventType,
                'properties_json' => $properties,
                'created_at' => $createdAt,
            ]);
        }
    }

    /**
     * Weight random days so more recent days have more events.
     */
    private function weightedRandomDays(): int
    {
        // Higher probability for recent days (0-2 days ago)
        $weights = [0 => 20, 1 => 18, 2 => 16, 3 => 12, 4 => 10, 5 => 8, 6 => 6, 7 => 4];
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;
        foreach ($weights as $day => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $day;
            }
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function productViewProperties($products, array $urls): array
    {
        $product = $products->random();

        return [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'url' => '/products/'.$product->handle,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function addToCartProperties($products): array
    {
        $product = $products->random();
        $variant = $product->variants->random();

        return [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => rand(1, 3),
            'price_amount' => $variant->price_amount,
        ];
    }
}
