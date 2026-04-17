<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(DefaultStoreSeeder::class);
        $this->call(ThemeSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(NavigationSeeder::class);
        $this->call(CatalogSeeder::class);
    }
}
