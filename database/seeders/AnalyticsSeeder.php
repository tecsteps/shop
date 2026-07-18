<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('analytics_daily') || ! Schema::hasTable('analytics_events')) {
            return;
        }

        DB::transaction(function (): void {
            $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $customers = Customer::query()->where('store_id', $store->id)->pluck('id');
            $products = Product::query()->where('store_id', $store->id)->where('status', 'active')->get();

            mt_srand(1001);

            for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
                $factor = 1 + (30 - $daysAgo) * 0.03;
                $visits = $daysAgo === 0 ? mt_rand(80, 110) : (int) round(mt_rand(50, 100) * $factor);
                $addToCart = (int) round($visits * mt_rand(18, 25) / 100);
                $checkoutStarted = (int) round($addToCart * mt_rand(40, 55) / 100);
                $orders = max(2, (int) round($checkoutStarted * mt_rand(35, 55) / 100));
                $averageOrderValue = mt_rand(4000, 9000);

                DB::table('analytics_daily')->updateOrInsert(
                    ['store_id' => $store->id, 'date' => now()->subDays($daysAgo)->toDateString()],
                    [
                        'visits_count' => $visits,
                        'add_to_cart_count' => $addToCart,
                        'checkout_started_count' => $checkoutStarted,
                        'orders_count' => $orders,
                        'revenue_amount' => $orders * $averageOrderValue,
                        'aov_amount' => $averageOrderValue,
                    ],
                );
            }

            DB::table('analytics_events')->where('store_id', $store->id)->delete();
            $types = [
                ...array_fill(0, 88, 'page_view'),
                ...array_fill(0, 55, 'product_view'),
                ...array_fill(0, 33, 'add_to_cart'),
                ...array_fill(0, 22, 'checkout_started'),
                ...array_fill(0, 11, 'checkout_completed'),
                ...array_fill(0, 11, 'search'),
            ];
            $sessions = collect(range(1, 35))->map(fn (): string => (string) Str::uuid());

            foreach ($types as $index => $type) {
                $product = $products[$index % $products->count()];
                DB::table('analytics_events')->insert([
                    'store_id' => $store->id,
                    'type' => $type,
                    'session_id' => $sessions[$index % $sessions->count()],
                    'customer_id' => $index % 10 < 3 ? $customers[$index % $customers->count()] : null,
                    'properties_json' => json_encode($this->properties($type, $product), JSON_THROW_ON_ERROR),
                    'created_at' => now()->subMinutes(mt_rand(0, 7 * 24 * 60)),
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function properties(string $type, Product $product): array
    {
        return match ($type) {
            'product_view' => ['product_id' => $product->id, 'product_title' => $product->title, 'url' => '/products/'.$product->handle],
            'add_to_cart' => ['product_id' => $product->id, 'variant_id' => $product->variants()->value('id'), 'quantity' => 1, 'price_amount' => $product->variants()->value('price_amount')],
            'checkout_started' => ['cart_id' => null, 'item_count' => 2, 'cart_total' => 5498],
            'checkout_completed' => ['order_id' => null, 'order_number' => '#1001', 'total_amount' => 5497],
            'search' => ['query' => 'cotton t-shirt', 'results_count' => 4],
            default => ['url' => '/', 'referrer' => 'https://www.google.com'],
        };
    }
}
