<?php

namespace Database\Seeders;

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AnalyticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $days = 14;

        for ($offset = $days; $offset >= 0; $offset--) {
            $date = Carbon::now()->subDays($offset)->startOfDay();

            $visits = random_int(50, 200);
            $productViews = random_int($visits, $visits * 3);
            $addToCart = (int) floor($visits * 0.2);
            $checkoutStarted = (int) floor($addToCart * 0.6);
            $checkoutCompleted = (int) floor($checkoutStarted * 0.5);

            $session = 'seed-sess-'.$offset;

            for ($i = 0; $i < $visits; $i++) {
                AnalyticsEvent::query()->create([
                    'store_id' => $store->getKey(),
                    'type' => AnalyticsEventType::PageView->value,
                    'session_id' => $session.'-'.$i,
                    'customer_id' => null,
                    'properties_json' => ['path' => '/'],
                    'client_event_id' => null,
                    'occurred_at' => $date->copy()->addMinutes(random_int(0, 1439)),
                    'created_at' => $date,
                ]);
            }

            for ($i = 0; $i < $productViews; $i++) {
                AnalyticsEvent::query()->create([
                    'store_id' => $store->getKey(),
                    'type' => AnalyticsEventType::ProductView->value,
                    'session_id' => $session.'-'.($i % $visits),
                    'customer_id' => null,
                    'properties_json' => ['product_id' => random_int(1, 18)],
                    'client_event_id' => null,
                    'occurred_at' => $date->copy()->addMinutes(random_int(0, 1439)),
                    'created_at' => $date,
                ]);
            }

            for ($i = 0; $i < $addToCart; $i++) {
                AnalyticsEvent::query()->create([
                    'store_id' => $store->getKey(),
                    'type' => AnalyticsEventType::AddToCart->value,
                    'session_id' => $session.'-'.($i % $visits),
                    'customer_id' => null,
                    'properties_json' => ['variant_id' => random_int(1, 50), 'quantity' => 1],
                    'client_event_id' => (string) Str::uuid(),
                    'occurred_at' => $date->copy()->addMinutes(random_int(0, 1439)),
                    'created_at' => $date,
                ]);
            }

            for ($i = 0; $i < $checkoutStarted; $i++) {
                AnalyticsEvent::query()->create([
                    'store_id' => $store->getKey(),
                    'type' => AnalyticsEventType::CheckoutStarted->value,
                    'session_id' => $session.'-'.($i % $visits),
                    'customer_id' => null,
                    'properties_json' => [],
                    'client_event_id' => null,
                    'occurred_at' => $date->copy()->addMinutes(random_int(0, 1439)),
                    'created_at' => $date,
                ]);
            }

            for ($i = 0; $i < $checkoutCompleted; $i++) {
                AnalyticsEvent::query()->create([
                    'store_id' => $store->getKey(),
                    'type' => AnalyticsEventType::CheckoutCompleted->value,
                    'session_id' => $session.'-'.($i % $visits),
                    'customer_id' => null,
                    'properties_json' => ['total_amount' => random_int(1000, 20_000)],
                    'client_event_id' => null,
                    'occurred_at' => $date->copy()->addMinutes(random_int(0, 1439)),
                    'created_at' => $date,
                ]);
            }

            $ordersCount = $checkoutCompleted;
            $revenue = $ordersCount * random_int(3_500, 9_000);
            $aov = $ordersCount > 0 ? (int) floor($revenue / $ordersCount) : 0;

            AnalyticsDaily::query()->updateOrCreate(
                ['store_id' => $store->getKey(), 'date' => $date->toDateString()],
                [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $aov,
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $checkoutCompleted,
                ],
            );
        }
    }
}
