<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->each(function (Store $store): void {
            Page::query()->firstOrCreate(
                ['store_id' => $store->getKey(), 'handle' => 'about'],
                [
                    'title' => 'About Us',
                    'body_html' => '<p>Welcome to '.e($store->name).'. We craft a curated selection of goods with care and attention to quality.</p>',
                    'status' => PageStatus::Published->value,
                    'published_at' => now(),
                ],
            );

            Page::query()->firstOrCreate(
                ['store_id' => $store->getKey(), 'handle' => 'contact'],
                [
                    'title' => 'Contact',
                    'body_html' => '<p>Questions or feedback? Email <a href="mailto:hello@shop.test">hello@shop.test</a>.</p>',
                    'status' => PageStatus::Published->value,
                    'published_at' => now(),
                ],
            );
        });
    }
}
