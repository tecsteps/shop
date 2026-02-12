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
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

        $this->seedDailyMetrics($fashion);
        $this->seedEvents($fashion);
    }

    private function seedDailyMetrics(Store $store): void
    {
        for ($i = 30; $i >= 0; $i--) {
            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(rand(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * rand(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * rand(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * rand(35, 55) / 100));
            $aov = rand(4000, 9000);
            $revenue = $orders * $aov;
            $date = now()->subDays($i)->toDateString();

            $metrics = [
                'visits' => $visits,
                'add_to_cart' => $addToCart,
                'checkout_started' => $checkoutStarted,
                'orders' => $orders,
                'revenue' => $revenue,
                'aov' => $aov,
            ];

            foreach ($metrics as $metric => $value) {
                AnalyticsDaily::withoutGlobalScopes()->firstOrCreate(
                    ['store_id' => $store->id, 'date' => $date, 'metric' => $metric, 'dimension' => null],
                    ['value' => $value],
                );
            }
        }
    }

    private function seedEvents(Store $store): void
    {
        $customers = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id')
            ->all();

        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('variants')
            ->get();

        $collectionHandles = ['new-arrivals', 't-shirts', 'pants-jeans', 'sale'];
        $searchQueries = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'slim fit', 'organic cotton'];

        // Build ~35 sessions with 5-10 events each
        $sessions = [];
        for ($s = 0; $s < 35; $s++) {
            $sessions[] = Str::uuid()->toString();
        }

        $eventTypes = [
            'page_view' => 88,
            'product_view' => 55,
            'add_to_cart' => 33,
            'checkout_started' => 22,
            'checkout_completed' => 11,
            'search' => 11,
        ];

        $events = [];

        foreach ($eventTypes as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $sessionId = $sessions[array_rand($sessions)];
                $customerId = (rand(1, 100) <= 30) ? $customers[array_rand($customers)] : null;
                $createdAt = now()->subDays(rand(0, 6))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                $properties = match ($type) {
                    'page_view' => $this->pageViewProperties($collectionHandles),
                    'product_view' => $this->productViewProperties($products),
                    'add_to_cart' => $this->addToCartProperties($products),
                    'checkout_started' => ['cart_id' => rand(1, 50), 'item_count' => rand(1, 5), 'cart_total' => rand(2000, 15000)],
                    'checkout_completed' => ['order_id' => rand(1, 20), 'order_number' => '#'.rand(1001, 1015), 'total_amount' => rand(2000, 15000)],
                    'search' => ['query' => $searchQueries[array_rand($searchQueries)], 'results_count' => rand(0, 25)],
                };

                $events[] = [
                    'store_id' => $store->id,
                    'event_type' => $type,
                    'properties_json' => json_encode($properties),
                    'session_id' => $sessionId,
                    'customer_id' => $customerId,
                    'created_at' => $createdAt,
                ];
            }
        }

        // Bulk insert in chunks
        foreach (array_chunk($events, 50) as $chunk) {
            AnalyticsEvent::withoutGlobalScopes()->insert($chunk);
        }
    }

    /**
     * @param  list<string>  $collectionHandles
     * @return array<string, string|null>
     */
    private function pageViewProperties(array $collectionHandles): array
    {
        $urls = ['/', '/collections/'.$collectionHandles[array_rand($collectionHandles)], '/cart', '/account'];
        $referrers = [null, 'https://www.google.com', 'https://www.instagram.com', null, null];

        $props = ['url' => $urls[array_rand($urls)]];
        $referrer = $referrers[array_rand($referrers)];
        if ($referrer !== null) {
            $props['referrer'] = $referrer;
        }

        return $props;
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
            'price_amount' => $variant?->price_amount ?? 2999,
        ];
    }
}
