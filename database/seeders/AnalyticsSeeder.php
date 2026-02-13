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
    /**
     * Seed analytics daily aggregates and event stream for Acme Fashion.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::where('handle', 'acme-fashion')->firstOrFail();

            $this->seedDailyData($store);
            $this->seedEvents($store);
        });
    }

    private function seedDailyData(Store $store): void
    {
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            if (AnalyticsDaily::withoutGlobalScopes()->where('store_id', $store->id)->where('date', $date)->exists()) {
                continue;
            }

            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(rand(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * rand(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * rand(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * rand(35, 55) / 100));
            $aov = rand(4000, 9000);
            $revenue = $orders * $aov;

            AnalyticsDaily::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'date' => $date,
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCart,
                'checkout_started_count' => $checkoutStarted,
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $aov,
            ]);
        }
    }

    private function seedEvents(Store $store): void
    {
        if (AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->id)->count() >= 200) {
            return;
        }

        $customers = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id')
            ->toArray();

        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->with('variants')
            ->get();

        $sessionIds = collect(range(1, 35))->map(fn (): string => Str::uuid()->toString())->toArray();

        $eventTypes = array_merge(
            array_fill(0, 88, 'page_view'),
            array_fill(0, 55, 'product_view'),
            array_fill(0, 33, 'add_to_cart'),
            array_fill(0, 22, 'checkout_started'),
            array_fill(0, 11, 'checkout_completed'),
            array_fill(0, 11, 'search'),
        );

        shuffle($eventTypes);

        $urls = ['/', '/collections/new-arrivals', '/collections/t-shirts', '/collections/pants-jeans', '/collections/sale', '/cart', '/checkout'];
        $searchQueries = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'belt', 'sneakers', 'scarf', 'polo', 'shorts'];
        $referrers = ['https://www.google.com', 'https://www.instagram.com', 'https://www.facebook.com', null, null, null, null];

        foreach ($eventTypes as $index => $type) {
            $sessionId = $sessionIds[$index % count($sessionIds)];
            $customerId = rand(1, 100) <= 30 ? $customers[array_rand($customers)] : null;
            $daysAgo = rand(0, 6);
            $createdAt = now()->subDays($daysAgo)->subMinutes(rand(0, 1440));

            $properties = match ($type) {
                'page_view' => [
                    'url' => $urls[array_rand($urls)],
                    'referrer' => $referrers[array_rand($referrers)],
                ],
                'product_view' => [
                    'product_id' => ($product = $products->random())->id,
                    'product_title' => $product->title,
                    'url' => '/products/'.$product->handle,
                ],
                'add_to_cart' => [
                    'product_id' => ($product = $products->random())->id,
                    'variant_id' => $product->variants->isNotEmpty() ? $product->variants->random()->id : null,
                    'quantity' => rand(1, 3),
                    'price_amount' => $product->variants->isNotEmpty() ? $product->variants->first()->price_amount : 0,
                ],
                'checkout_started' => [
                    'cart_id' => rand(1, 100),
                    'item_count' => rand(1, 5),
                    'cart_total' => rand(2000, 50000),
                ],
                'checkout_completed' => [
                    'order_id' => rand(1, 15),
                    'order_number' => '#'.rand(1001, 1015),
                    'total_amount' => rand(3000, 50000),
                ],
                'search' => [
                    'query' => $searchQueries[array_rand($searchQueries)],
                    'results_count' => rand(0, 20),
                ],
                default => [],
            };

            AnalyticsEvent::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'event_type' => $type,
                'properties_json' => $properties,
                'created_at' => $createdAt,
            ]);
        }
    }
}
