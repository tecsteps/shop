<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    /**
     * Create 30 days of daily aggregates and a stream of ~220 events over
     * the last 7 days for Acme Fashion (spec 07 §3.17), so the admin
     * analytics charts render. All values are deterministic.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

            $this->seedDaily($fashion);
            $this->seedEvents($fashion);
        });
    }

    /**
     * One analytics_daily row per day for the past 30 days through today,
     * with an upward revenue trend (spec 07 §3.17 algorithm).
     */
    private function seedDaily(Store $store): void
    {
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dayFactor = 1 + (30 - $i) * 0.03;

            $visits = (int) round($this->randomBetween(50, 100, $date.'visits') * $dayFactor);
            $addToCart = (int) round($visits * $this->randomBetween(18, 25, $date.'cart') / 100);
            $checkoutStarted = (int) round($addToCart * $this->randomBetween(40, 55, $date.'checkout') / 100);
            $orders = max(2, (int) round($checkoutStarted * $this->randomBetween(35, 55, $date.'orders') / 100));
            $aov = $this->randomBetween(4000, 9000, $date.'aov');

            DB::table('analytics_daily')->updateOrInsert(
                ['store_id' => $store->id, 'date' => $date],
                [
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $orders,
                    'orders_count' => $orders,
                    'revenue_amount' => $orders * $aov,
                    'aov_amount' => $aov,
                ],
            );
        }
    }

    /**
     * Deterministic "random" integer in [$min, $max] derived from a salt, so
     * re-seeding produces identical data without global RNG state.
     */
    private function randomBetween(int $min, int $max, string $salt): int
    {
        return $min + (int) (hexdec(substr(md5($salt), 0, 8)) % ($max - $min + 1));
    }

    /**
     * ~220 analytics events across the last 7 days with realistic session
     * grouping, type distribution, and customer association (spec 07 §3.17).
     */
    private function seedEvents(Store $store): void
    {
        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->with('variants')
            ->orderBy('id')
            ->get();

        $orders = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->get();

        $customerIds = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        // Event type distribution: 40/25/15/10/5/5 percent of 220 events.
        $types = array_merge(
            array_fill(0, 88, 'page_view'),
            array_fill(0, 55, 'product_view'),
            array_fill(0, 33, 'add_to_cart'),
            array_fill(0, 22, 'checkout_started'),
            array_fill(0, 11, 'checkout_completed'),
            array_fill(0, 11, 'search'),
        );

        // Interleave the types deterministically so each day gets a mix.
        $interleaved = [];
        $pools = array_count_values($types);
        $order = ['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed', 'search'];

        while (count($interleaved) < 220) {
            foreach ($order as $type) {
                if (($pools[$type] ?? 0) > 0) {
                    $interleaved[] = $type;
                    $pools[$type]--;
                }
            }
        }

        // Events per day, oldest to newest - more events on recent days.
        $perDay = [20, 24, 28, 32, 36, 40, 40];

        $eventIndex = 0;

        foreach ($perDay as $daysAgo => $count) {
            for ($k = 0; $k < $count; $k++) {
                $type = $interleaved[$eventIndex];
                $occurredAt = now()
                    ->subDays(6 - $daysAgo)
                    ->setTime(7 + ($eventIndex % 15), ($eventIndex * 7) % 60, ($eventIndex * 13) % 60);

                // forceFill: created_at is not mass assignable.
                AnalyticsEvent::query()
                    ->firstOrNew(['store_id' => $store->id, 'client_event_id' => 'seed-'.$store->id.'-event-'.$eventIndex])
                    ->forceFill([
                        'store_id' => $store->id,
                        'type' => $type,
                        'session_id' => 'seed-session-'.str_pad((string) ($eventIndex % 35 + 1), 2, '0', STR_PAD_LEFT),
                        'customer_id' => $eventIndex % 10 < 3 && $customerIds !== []
                            ? $customerIds[$eventIndex % count($customerIds)]
                            : null,
                        'properties_json' => $this->properties($type, $eventIndex, $products, $orders),
                        'occurred_at' => $occurredAt->toIso8601ZuluString(),
                        'created_at' => $occurredAt,
                    ])
                    ->save();

                $eventIndex++;
            }
        }
    }

    /**
     * Build the properties payload for one event (spec 07 §3.17).
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    private function properties(string $type, int $index, $products, $orders): array
    {
        $urls = ['/', '/collections/new-arrivals', '/collections/t-shirts', '/collections/sale', '/collections/pants-jeans', '/products/classic-cotton-t-shirt'];

        return match ($type) {
            'page_view' => [
                'url' => $urls[$index % count($urls)],
                'referrer' => $index % 10 < 4 ? 'https://www.google.com' : null,
            ],
            'product_view' => $this->productViewProperties($products, $index),
            'add_to_cart' => $this->addToCartProperties($products, $index),
            'checkout_started' => [
                'cart_id' => 1000 + $index,
                'item_count' => $index % 4 + 1,
                'cart_total' => 2499 * ($index % 4 + 1),
            ],
            'checkout_completed' => $this->checkoutCompletedProperties($orders, $index),
            'search' => [
                'query' => ['cotton t-shirt', 'jeans', 'gift card', 'hoodie', 'sneakers'][$index % 5],
                'results_count' => $index % 12 + 1,
            ],
            default => [],
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function productViewProperties($products, int $index): array
    {
        $product = $products[$index % $products->count()];

        return [
            'product_id' => $product->id,
            'product_title' => $product->title,
            'url' => '/products/'.$product->handle,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function addToCartProperties($products, int $index): array
    {
        $product = $products[$index % $products->count()];
        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

        return [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $index % 3 + 1,
            'price_amount' => $variant?->price_amount ?? 0,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    private function checkoutCompletedProperties($orders, int $index): array
    {
        $order = $orders[$index % $orders->count()];

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount,
        ];
    }
}
