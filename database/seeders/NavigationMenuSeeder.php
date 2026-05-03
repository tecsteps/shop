<?php

namespace Database\Seeders;

use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            foreach ([
                'main-menu' => 'Main Menu',
                'footer-menu' => 'Footer Menu',
            ] as $handle => $title) {
                NavigationMenu::withoutGlobalScopes()->updateOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'handle' => $handle,
                    ],
                    ['title' => $title],
                );
            }
        });
    }
}
