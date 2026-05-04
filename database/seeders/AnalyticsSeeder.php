<?php

namespace Database\Seeders;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            $this->seedDailyMetrics($store);

            if ($store->handle === 'acme-fashion') {
                $this->seedEventStream($store);
            }
        });
    }

    private function seedDailyMetrics(Store $store): void
    {
        foreach (range(0, 29) as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            $orders = max(0, 10 - ($daysAgo % 6));
            $revenue = $orders * (5200 + (($daysAgo % 5) * 800));

            DB::table('analytics_daily')->updateOrInsert(
                [
                    'store_id' => $store->getKey(),
                    'date' => $date,
                ],
                [
                    'orders_count' => $orders,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
                    'visits_count' => 160 + (($daysAgo * 7) % 80),
                    'add_to_cart_count' => 42 + ($daysAgo % 13),
                    'checkout_started_count' => 18 + ($daysAgo % 8),
                    'checkout_completed_count' => $orders,
                ],
            );
        }
    }

    private function seedEventStream(Store $store): void
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->first();

        $types = [
            AnalyticsEventType::PageView,
            AnalyticsEventType::PageView,
            AnalyticsEventType::PageView,
            AnalyticsEventType::ProductView,
            AnalyticsEventType::ProductView,
            AnalyticsEventType::AddToCart,
            AnalyticsEventType::CheckoutStarted,
            AnalyticsEventType::CheckoutCompleted,
            AnalyticsEventType::Search,
            AnalyticsEventType::RemoveFromCart,
        ];

        $referrers = [
            'https://google.com/search?q=acme+fashion',
            'https://instagram.com/acme-fashion',
            'direct',
            'https://newsletter.example/acme',
        ];

        foreach (range(1, 210) as $index) {
            $type = $types[$index % count($types)];
            $occurredAt = now()
                ->subDays($index % 7)
                ->setTime($index % 24, ($index * 7) % 60, 0);

            AnalyticsEvent::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'client_event_id' => "seed_evt_{$store->handle}_{$index}",
                ],
                [
                    'type' => $type,
                    'session_id' => 'seed_sess_'.($index % 48),
                    'customer_id' => $index % 5 === 0 ? $customer?->getKey() : null,
                    'properties_json' => $this->propertiesFor($type, $index, $referrers[$index % count($referrers)]),
                    'occurred_at' => $occurredAt,
                    'created_at' => $occurredAt,
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesFor(AnalyticsEventType $type, int $index, string $referrer): array
    {
        $base = [
            'channel' => $index % 4 === 0 ? 'api' : 'storefront',
            'device' => ['desktop', 'mobile', 'tablet'][$index % 3],
            'referrer' => $referrer,
        ];

        return match ($type) {
            AnalyticsEventType::PageView => [
                ...$base,
                'url' => $index % 2 === 0 ? '/' : '/collections/t-shirts',
            ],
            AnalyticsEventType::ProductView => [
                ...$base,
                'product_id' => ($index % 20) + 1,
                'product_title' => 'Seeded product '.$index,
                'url' => '/products/classic-cotton-t-shirt',
            ],
            AnalyticsEventType::AddToCart, AnalyticsEventType::RemoveFromCart => [
                ...$base,
                'product_id' => ($index % 20) + 1,
                'variant_id' => ($index % 120) + 1,
                'quantity' => 1 + ($index % 3),
                'price_amount' => 2499 + (($index % 5) * 500),
            ],
            AnalyticsEventType::CheckoutStarted => [
                ...$base,
                'cart_id' => $index,
                'subtotal_amount' => 4500 + (($index % 9) * 500),
            ],
            AnalyticsEventType::CheckoutCompleted => [
                ...$base,
                'order_id' => $index,
                'order_number' => 'SEED-'.$index,
                'total_amount' => 6500 + (($index % 9) * 750),
            ],
            AnalyticsEventType::Search => [
                ...$base,
                'query' => ['cotton', 'hoodie', 'jeans', 'sneakers'][$index % 4],
                'results_count' => 3 + ($index % 8),
            ],
        };
    }
}
