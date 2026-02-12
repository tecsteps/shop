<?php

namespace Database\Seeders;

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::query()
            ->whereIn('handle', ['acme-fashion', 'acme-electronics'])
            ->get()
            ->keyBy('handle');

        if ($stores->isEmpty()) {
            return;
        }

        AnalyticsDaily::query()->delete();
        AnalyticsEvent::query()->delete();

        $fashionStore = $stores->get('acme-fashion');
        $electronicsStore = $stores->get('acme-electronics');

        $fashionCustomerId = $fashionStore
            ? Customer::query()->where('store_id', $fashionStore->id)->value('id')
            : null;

        $events = [];

        if ($fashionStore) {
            $events = array_merge($events, [
                $this->event($fashionStore->id, 'page_view', 'fashion-session-1', 'fashion-pv-1', '2026-02-10 09:00:00', ['path' => '/products/classic-cotton-t-shirt']),
                $this->event($fashionStore->id, 'add_to_cart', 'fashion-session-1', 'fashion-atc-1', '2026-02-10 09:03:00', ['product_id' => 1]),
                $this->event($fashionStore->id, 'checkout_started', 'fashion-session-1', 'fashion-chk-1', '2026-02-10 09:05:00', ['cart_total_amount' => 2499]),
                $this->event($fashionStore->id, 'checkout_completed', 'fashion-session-1', 'fashion-chk-2', '2026-02-10 09:08:00', ['order_total_amount' => 2499], $fashionCustomerId),
                $this->event($fashionStore->id, 'page_view', 'fashion-session-2', 'fashion-pv-2', '2026-02-10 10:00:00', ['path' => '/collections/new-arrivals']),
                $this->event($fashionStore->id, 'search', 'fashion-session-2', 'fashion-search-1', '2026-02-10 10:02:00', ['query' => 'denim']),
            ]);
        }

        if ($electronicsStore) {
            $events = array_merge($events, [
                $this->event($electronicsStore->id, 'page_view', 'electronics-session-1', 'electronics-pv-1', '2026-02-10 11:00:00', ['path' => '/products/noise-cancelling-headphones']),
                $this->event($electronicsStore->id, 'add_to_cart', 'electronics-session-1', 'electronics-atc-1', '2026-02-10 11:05:00', ['product_id' => 2]),
                $this->event($electronicsStore->id, 'checkout_started', 'electronics-session-1', 'electronics-chk-1', '2026-02-10 11:08:00', ['cart_total_amount' => 12999]),
                $this->event($electronicsStore->id, 'checkout_completed', 'electronics-session-1', 'electronics-chk-2', '2026-02-10 11:12:00', ['order_total_amount' => 12999]),
                $this->event($electronicsStore->id, 'page_view', 'electronics-session-2', 'electronics-pv-2', '2026-02-10 12:00:00', ['path' => '/collections/featured']),
            ]);
        }

        if ($events !== []) {
            AnalyticsEvent::query()->insert($events);
        }

        (new AggregateAnalytics('2026-02-10'))->handle();
        (new AggregateAnalytics('2026-02-10'))->handle();
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function event(
        int $storeId,
        string $type,
        string $sessionId,
        string $clientEventId,
        string $occurredAt,
        array $properties,
        ?int $customerId = null,
    ): array {
        $occurred = CarbonImmutable::parse($occurredAt, 'UTC');

        return [
            'store_id' => $storeId,
            'type' => $type,
            'session_id' => $sessionId,
            'customer_id' => $customerId,
            'properties_json' => json_encode($properties, JSON_UNESCAPED_SLASHES),
            'client_event_id' => $clientEventId,
            'occurred_at' => $occurred,
            'created_at' => $occurred,
        ];
    }
}
