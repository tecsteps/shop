<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    /**
     * Seed 30 days of daily aggregates and a stream of analytics events
     * for the Acme Fashion store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            mt_srand(20250101);

            $store = Store::where('handle', 'acme-fashion')->firstOrFail();

            $this->seedDaily($store);
            $this->seedEvents($store);
        });
    }

    private function seedDaily(Store $store): void
    {
        // The AnalyticsDaily model resolves the wrong table name, so the
        // query builder targets the actual "analytics_daily" table directly.
        for ($i = 30; $i >= 0; $i--) {
            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(mt_rand(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * mt_rand(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * mt_rand(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * mt_rand(35, 55) / 100));
            $aov = mt_rand(4000, 9000);
            $revenue = $orders * $aov;

            DB::table('analytics_daily')->updateOrInsert(
                ['store_id' => $store->id, 'date' => now()->subDays($i)->format('Y-m-d')],
                [
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $orders,
                    'orders_count' => $orders,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $aov,
                ],
            );
        }
    }

    private function seedEvents(Store $store): void
    {
        $customerIds = Customer::where('store_id', $store->id)->pluck('id')->all();

        $products = Product::where('store_id', $store->id)->where('status', 'active')->get();
        $variants = ProductVariant::with('product')
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
            ->get()
            ->groupBy('product_id');

        $sessionCount = mt_rand(30, 40);
        $eventIndex = 0;

        for ($session = 0; $session < $sessionCount; $session++) {
            $sessionId = (string) Str::uuid();
            $eventCount = mt_rand(5, 10);

            for ($i = 0; $i < $eventCount; $i++) {
                $type = $this->randomEventType();

                $properties = match ($type) {
                    'page_view' => [
                        'url' => $this->randomPageUrl(),
                        'referrer' => mt_rand(1, 100) <= 40 ? 'https://www.google.com' : null,
                    ],
                    'product_view' => $this->randomProductProperties($products, withVariant: false),
                    'add_to_cart' => $this->randomProductProperties($products, $variants, withVariant: true),
                    'checkout_started' => [
                        'cart_id' => mt_rand(1000, 9999),
                        'item_count' => mt_rand(1, 4),
                        'cart_total' => mt_rand(2000, 60000),
                    ],
                    'checkout_completed' => [
                        'order_id' => mt_rand(1001, 1015),
                        'order_number' => '#'.mt_rand(1001, 1015),
                        'total_amount' => mt_rand(3000, 60000),
                    ],
                    default => [
                        'query' => $this->randomSearchQuery(),
                        'results_count' => mt_rand(0, 24),
                    ],
                };

                AnalyticsEvent::firstOrCreate(
                    ['store_id' => $store->id, 'client_event_id' => 'seed-'.$store->handle.'-'.$session.'-'.$i],
                    [
                        'type' => $type,
                        'session_id' => $sessionId,
                        'customer_id' => mt_rand(1, 100) <= 30 && $customerIds !== []
                            ? $customerIds[array_rand($customerIds)]
                            : null,
                        'properties_json' => $properties,
                        'occurred_at' => $this->randomOccurredAt(),
                    ],
                );

                $eventIndex++;
            }
        }

        $this->command?->info("Seeded {$eventIndex} analytics events for {$store->handle}.");
    }

    private function randomEventType(): string
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 40 => 'page_view',
            $roll <= 65 => 'product_view',
            $roll <= 80 => 'add_to_cart',
            $roll <= 90 => 'checkout_started',
            $roll <= 95 => 'checkout_completed',
            default => 'search',
        };
    }

    private function randomPageUrl(): string
    {
        $urls = [
            '/',
            '/collections/new-arrivals',
            '/collections/t-shirts',
            '/collections/pants-jeans',
            '/collections/sale',
            '/pages/about',
            '/pages/faq',
            '/pages/shipping-returns',
        ];

        return $urls[array_rand($urls)];
    }

    private function randomSearchQuery(): string
    {
        $queries = ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'sneakers', 'scarf', 'overcoat'];

        return $queries[array_rand($queries)];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @param  \Illuminate\Support\Collection<int, ProductVariant>|null  $variants
     * @return array<string, mixed>
     */
    private function randomProductProperties($products, $variants = null, bool $withVariant = false): array
    {
        if ($products->isEmpty()) {
            return ['url' => '/'];
        }

        $product = $products->random();

        $properties = [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'url' => '/products/'.$product->handle,
        ];

        if ($withVariant && $variants !== null && $variants->has($product->id)) {
            $variant = $variants[$product->id]->first();

            $properties += [
                'variant_id' => $variant->id,
                'quantity' => mt_rand(1, 2),
                'price_amount' => $variant->price_amount,
            ];
        }

        return $properties;
    }

    private function randomOccurredAt(): CarbonInterface
    {
        $roll = mt_rand(1, 100);

        $dayOffset = match (true) {
            $roll <= 35 => mt_rand(0, 1),
            $roll <= 70 => mt_rand(0, 3),
            default => mt_rand(0, 6),
        };

        return now()
            ->subDays($dayOffset)
            ->subMinutes(mt_rand(0, 1439))
            ->subSeconds(mt_rand(0, 59));
    }
}
