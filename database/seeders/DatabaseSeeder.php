<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FoundationSeeder::class,
            CatalogSeeder::class,
            CommerceSeeder::class,
            SearchAnalyticsSeeder::class,
            AppWebhookSeeder::class,
        ]);
    }
}
