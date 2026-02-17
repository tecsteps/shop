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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

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
        $customers = Customer::query()
            ->where('store_id', $store->id)
            ->pluck('id')
            ->toArray();

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->with('variants')
            ->get();

        $pages = ['/', '/collections/t-shirts', '/collections/new-arrivals', '/collections/pants-jeans', '/collections/sale', '/products/classic-cotton-t-shirt', '/products/premium-slim-fit-jeans'];
        $searchQueries = ['cotton t-shirt', 'jeans', 'gift card', 'sneakers', 'hoodie', 'belt', 'scarf'];
        $referrers = ['https://www.google.com', 'https://www.instagram.com', 'https://www.facebook.com', null, null, null];

        // Generate ~35 sessions with 5-8 events each
        $sessionCount = 35;
        $totalEvents = 0;

        for ($s = 0; $s < $sessionCount; $s++) {
            $sessionId = Str::uuid()->toString();
            $eventsInSession = rand(5, 8);
            $hasCustomer = rand(1, 100) <= 30;
            $customerId = $hasCustomer ? $customers[array_rand($customers)] : null;

            // Session starts at a random time in the last 7 days, weighted toward recent
            $daysAgo = $this->weightedRandom(0, 6);
            $sessionStart = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            for ($e = 0; $e < $eventsInSession; $e++) {
                $eventTime = $sessionStart->copy()->addMinutes($e * rand(1, 5));
                $eventType = $this->pickEventType($e, $eventsInSession);
                $properties = $this->buildEventProperties($eventType, $products, $pages, $searchQueries, $referrers);

                AnalyticsEvent::create([
                    'store_id' => $store->id,
                    'type' => $eventType,
                    'session_id' => $sessionId,
                    'customer_id' => $customerId,
                    'properties_json' => $properties,
                    'occurred_at' => $eventTime->toDateTimeString(),
                    'created_at' => $eventTime,
                    'updated_at' => $eventTime,
                ]);

                $totalEvents++;
            }
        }
    }

    private function pickEventType(int $position, int $total): string
    {
        // Early events are page views, later events progress through the funnel
        if ($position === 0) {
            return 'page_view';
        }

        $progress = $position / $total;

        if ($progress < 0.3) {
            return collect(['page_view', 'product_view', 'search'])->random();
        }

        if ($progress < 0.6) {
            return collect(['product_view', 'add_to_cart', 'page_view'])->random();
        }

        if ($progress < 0.85) {
            return collect(['add_to_cart', 'checkout_started', 'product_view'])->random();
        }

        return collect(['checkout_started', 'checkout_completed'])->random();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @param  list<string>  $pages
     * @param  list<string>  $searchQueries
     * @param  list<string|null>  $referrers
     * @return array<string, mixed>
     */
    private function buildEventProperties(string $type, $products, array $pages, array $searchQueries, array $referrers): array
    {
        return match ($type) {
            'page_view' => [
                'url' => $pages[array_rand($pages)],
                'referrer' => $referrers[array_rand($referrers)],
            ],
            'product_view' => $this->productViewProperties($products, $referrers),
            'add_to_cart' => $this->addToCartProperties($products),
            'checkout_started' => [
                'cart_id' => rand(1, 100),
                'item_count' => rand(1, 4),
                'cart_total' => rand(2000, 15000),
            ],
            'checkout_completed' => [
                'order_id' => rand(1, 100),
                'order_number' => '#'.rand(1001, 1999),
                'total_amount' => rand(3000, 20000),
            ],
            'search' => [
                'query' => $searchQueries[array_rand($searchQueries)],
                'results_count' => rand(0, 20),
            ],
            default => [],
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @param  list<string|null>  $referrers
     * @return array<string, mixed>
     */
    private function productViewProperties($products, array $referrers): array
    {
        $product = $products->random();

        return [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'url' => '/products/'.$product->handle,
            'referrer' => $referrers[array_rand($referrers)],
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
            'price_amount' => $variant?->price_amount ?? 2999,
        ];
    }

    private function weightedRandom(int $min, int $max): int
    {
        // Weighted toward lower values (more recent days)
        $range = $max - $min;
        $random = pow(mt_rand() / mt_getrandmax(), 0.5) * $range;

        return $min + (int) round($random);
    }
}
