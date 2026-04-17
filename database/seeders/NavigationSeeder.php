<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->each(function (Store $store): void {
            $menu = NavigationMenu::query()->firstOrCreate(
                ['store_id' => $store->getKey(), 'handle' => 'main-menu'],
                ['title' => 'Main menu'],
            );

            $about = Page::query()
                ->where('store_id', $store->getKey())
                ->where('handle', 'about')
                ->first();

            $contact = Page::query()
                ->where('store_id', $store->getKey())
                ->where('handle', 'contact')
                ->first();

            $items = [
                [
                    'type' => NavigationItemType::Link->value,
                    'label' => 'Home',
                    'url' => '/',
                    'resource_id' => null,
                    'position' => 0,
                ],
                [
                    'type' => NavigationItemType::Link->value,
                    'label' => 'Shop',
                    'url' => '/collections/all',
                    'resource_id' => null,
                    'position' => 1,
                ],
            ];

            if ($about !== null) {
                $items[] = [
                    'type' => NavigationItemType::Page->value,
                    'label' => 'About',
                    'url' => null,
                    'resource_id' => $about->getKey(),
                    'position' => 2,
                ];
            }

            if ($contact !== null) {
                $items[] = [
                    'type' => NavigationItemType::Page->value,
                    'label' => 'Contact',
                    'url' => null,
                    'resource_id' => $contact->getKey(),
                    'position' => 3,
                ];
            }

            foreach ($items as $data) {
                NavigationItem::query()->firstOrCreate(
                    [
                        'menu_id' => $menu->getKey(),
                        'label' => $data['label'],
                    ],
                    $data,
                );
            }
        });
    }
}
