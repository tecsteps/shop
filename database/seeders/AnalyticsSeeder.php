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
    /**
     * Seed 30 days of upward-trending daily aggregates and a realistic raw
     * event stream over the last 7 days for Acme Fashion (spec 07 section
     * 3.17).
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        if (AnalyticsDaily::query()->where('store_id', $store->getKey())->exists()) {
            return;
        }

        $this->seedDailyAggregates($store);
        $this->seedEventStream($store);
    }

    /**
     * One analytics_daily row per day for the past 30 days with roughly 3%
     * daily growth.
     */
    protected function seedDailyAggregates(Store $store): void
    {
        $rows = [];

        for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
            $dayFactor = 1 + (30 - $daysAgo) * 0.03;
            $visits = (int) round(random_int(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * random_int(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * random_int(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * random_int(35, 55) / 100));
            $aov = random_int(4000, 9000);

            $rows[] = [
                'store_id' => $store->getKey(),
                'date' => now()->subDays($daysAgo)->toDateString(),
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCart,
                'checkout_started_count' => $checkoutStarted,
                'checkout_completed_count' => $orders,
                'orders_count' => $orders,
                'revenue_amount' => $orders * $aov,
                'aov_amount' => $aov,
            ];
        }

        AnalyticsDaily::query()->insert($rows);
    }

    /**
     * Roughly 220 events across ~35 sessions over the last 7 days, with the
     * spec's type distribution and 30% of events tied to a customer.
     */
    protected function seedEventStream(Store $store): void
    {
        $products = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get(['id', 'title', 'handle']);

        $customerIds = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->pluck('id');

        $referrers = ['https://www.google.com', 'https://www.instagram.com', 'https://news.example.com', null, null];
        $searchTerms = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'summer dress'];

        $typePlan = array_merge(
            array_fill(0, 88, 'page_view'),
            array_fill(0, 55, 'product_view'),
            array_fill(0, 33, 'add_to_cart'),
            array_fill(0, 22, 'checkout_started'),
            array_fill(0, 11, 'checkout_completed'),
            array_fill(0, 11, 'search'),
        );

        shuffle($typePlan);

        $sessions = [];

        for ($index = 0; $index < 35; $index++) {
            $sessions[] = [
                'id' => 'sess_'.Str::uuid(),
                'referrer' => $referrers[array_rand($referrers)],
                'customer_id' => random_int(1, 100) <= 30 && $customerIds->isNotEmpty()
                    ? $customerIds->random()
                    : null,
            ];
        }

        $rows = [];

        foreach ($typePlan as $type) {
            $session = $sessions[array_rand($sessions)];
            $createdAt = now()
                ->subDays(random_int(0, 100) < 55 ? random_int(0, 2) : random_int(3, 6))
                ->subMinutes(random_int(0, 1439));
            $product = $products->isNotEmpty() ? $products->random() : null;

            $rows[] = [
                'store_id' => $store->getKey(),
                'type' => $type,
                'session_id' => $session['id'],
                'customer_id' => $session['customer_id'],
                'client_event_id' => 'evt_'.Str::uuid(),
                'properties_json' => json_encode($this->propertiesFor($type, $product, $session['referrer'], $searchTerms)),
                'occurred_at' => $createdAt,
                'created_at' => $createdAt,
            ];
        }

        AnalyticsEvent::query()->insert($rows);
    }

    /**
     * @param  list<string>  $searchTerms
     * @return array<string, mixed>
     */
    protected function propertiesFor(string $type, ?Product $product, ?string $referrer, array $searchTerms): array
    {
        return match ($type) {
            'page_view' => array_filter([
                'url' => collect(['/', '/collections/t-shirts', '/collections/new-arrivals', '/pages/about'])->random(),
                'referrer' => $referrer,
            ], fn (mixed $value): bool => $value !== null),
            'product_view' => [
                'product_id' => $product?->getKey(),
                'product_title' => $product?->title,
                'url' => '/products/'.($product?->handle ?? 'unknown'),
            ],
            'add_to_cart' => [
                'product_id' => $product?->getKey(),
                'variant_id' => random_int(1, 200),
                'quantity' => random_int(1, 3),
                'price_amount' => random_int(1500, 9000),
            ],
            'checkout_started' => [
                'cart_id' => random_int(1, 50),
                'item_count' => random_int(1, 4),
                'cart_total' => random_int(2500, 15000),
            ],
            'checkout_completed' => [
                'order_id' => random_int(1, 30),
                'order_number' => '#10'.random_int(10, 99),
                'total_amount' => random_int(2500, 15000),
            ],
            default => [
                'query' => $searchTerms[array_rand($searchTerms)],
                'results_count' => random_int(0, 12),
            ],
        };
    }
}
