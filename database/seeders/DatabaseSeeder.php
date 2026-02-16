<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            StoreSeeder::class,
            StoreDomainSeeder::class,
            UserSeeder::class,
            StoreUserSeeder::class,
            StoreSettingsSeeder::class,
            TaxSettingsSeeder::class,
            ShippingSeeder::class,
            CollectionSeeder::class,
            ProductSeeder::class,
            DiscountSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            ThemeSeeder::class,
            PageSeeder::class,
            NavigationMenuSeeder::class,
            AnalyticsSeeder::class,
            SearchSettingsSeeder::class,
        ]);

        $this->syncSearchIndex();
    }

    private function syncSearchIndex(): void
    {
        $searchService = app(SearchService::class);

        $stores = Store::all();
        foreach ($stores as $store) {
            $products = Product::where('store_id', $store->id)
                ->where('status', ProductStatus::Active)
                ->get();

            foreach ($products as $product) {
                $searchService->syncProduct($product);
            }
        }
    }
}
