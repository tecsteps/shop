<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DefaultStoreSeeder::class);
        $this->call(AdminUsersSeeder::class);
        $this->call(ThemeSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(NavigationSeeder::class);
        $this->call(CatalogSeeder::class);
        $this->call(CommerceSeeder::class);
        $this->call(CustomersSeeder::class);
        $this->call(OrdersSeeder::class);
        $this->call(AnalyticsDemoSeeder::class);
        $this->call(WebhooksDemoSeeder::class);
    }
}
