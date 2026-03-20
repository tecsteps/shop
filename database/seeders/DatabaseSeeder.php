<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@acme.test',
        ]);

        $this->call([
            OrganizationSeeder::class,
            StoreSeeder::class,
            CustomerSeeder::class,
            ThemeSeeder::class,
            PageSeeder::class,
            NavigationSeeder::class,
            ShippingZoneSeeder::class,
            TaxSettingsSeeder::class,
            DiscountSeeder::class,
        ]);
    }
}
