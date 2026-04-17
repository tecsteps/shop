<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DemoStoreSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
            CustomersAndOrdersSeeder::class,
        ]);
    }
}
