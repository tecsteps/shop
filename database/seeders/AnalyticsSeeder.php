<?php

namespace Database\Seeders;

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::query()->where('handle', 'acme-fashion')->sole();
            $this->seedDaily($store);
            $this->seedEvents($store);
        });
    }

    private function seedDaily(Store $store): void
    {
        $table = (new AnalyticsDaily)->getTable();
        for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
            $growth = 1 + (30 - $daysAgo) * 0.03;
            $visits = $daysAgo === 0 ? 96 : (int) round((60 + (($daysAgo * 17) % 41)) * $growth);
            $addToCart = (int) round($visits * (18 + ($daysAgo % 8)) / 100);
            $checkoutStarted = (int) round($addToCart * (40 + ($daysAgo % 16)) / 100);
            $orders = $daysAgo === 0 ? 3 : max(2, (int) round($checkoutStarted * (35 + ($daysAgo % 21)) / 100));
            $aov = 4000 + (($daysAgo * 347) % 5001);

            DB::table($table)->updateOrInsert(
                ['store_id' => $store->id, 'date' => now()->subDays($daysAgo)->toDateString()],
                [
                    'orders_count' => $orders,
                    'revenue_amount' => $orders * $aov,
                    'aov_amount' => $aov,
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $orders,
                ],
            );
        }
    }

    private function seedEvents(Store $store): void
    {
        $customers = Customer::withoutGlobalScopes()->where('store_id', $store->id)->orderBy('id')->get();
        $products = Product::withoutGlobalScopes()->where('store_id', $store->id)->where('status', 'active')->with('variants')->get();
        $orders = Order::withoutGlobalScopes()->where('store_id', $store->id)->orderBy('id')->get();
        $types = [
            ...array_fill(0, 88, 'page_view'), ...array_fill(0, 55, 'product_view'),
            ...array_fill(0, 33, 'add_to_cart'), ...array_fill(0, 22, 'checkout_started'),
            ...array_fill(0, 11, 'checkout_completed'), ...array_fill(0, 11, 'search'),
        ];
        $table = (new AnalyticsEvent)->getTable();

        foreach ($types as $index => $type) {
            $product = $products[$index % $products->count()];
            $variant = $product->variants[$index % $product->variants->count()];
            $order = $orders[$index % $orders->count()];
            $occurredAt = now()->subDays($index % 7)->subMinutes(($index * 19) % 1440);
            DB::table($table)->updateOrInsert(
                ['store_id' => $store->id, 'client_event_id' => 'seed-event-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'type' => $type,
                    'session_id' => 'seed-session-'.str_pad((string) (($index % 35) + 1), 2, '0', STR_PAD_LEFT),
                    'customer_id' => $index % 10 < 3 ? $customers[$index % $customers->count()]->id : null,
                    'properties_json' => json_encode($this->properties($type, $product, $variant, $order), JSON_THROW_ON_ERROR),
                    'occurred_at' => $occurredAt,
                    'created_at' => $occurredAt,
                ],
            );
        }
    }

    /** @return array<string, mixed> */
    private function properties(string $type, Product $product, mixed $variant, Order $order): array
    {
        return match ($type) {
            'page_view' => ['url' => '/', 'referrer' => 'https://www.google.com'],
            'product_view' => ['product_id' => $product->id, 'product_title' => $product->title, 'url' => '/products/'.$product->handle],
            'add_to_cart' => ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1, 'price_amount' => $variant->price_amount],
            'checkout_started' => ['cart_id' => 'seed-cart-'.$order->id, 'item_count' => 1, 'cart_total' => $order->subtotal_amount],
            'checkout_completed' => ['order_id' => $order->id, 'order_number' => $order->order_number, 'total_amount' => $order->total_amount],
            'search' => ['query' => ['cotton t-shirt', 'jeans', 'gift card'][$order->id % 3], 'results_count' => 5],
        };
    }
}
