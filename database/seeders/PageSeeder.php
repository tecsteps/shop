<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();

        foreach ($stores as $store) {
            Page::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'handle' => 'about',
                ],
                [
                    'title' => 'About Us',
                    'body_html' => '<p>We build products customers love.</p>',
                    'status' => 'published',
                    'published_at' => now()->subDays(3),
                ]
            );

            Page::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'handle' => 'contact',
                ],
                [
                    'title' => 'Contact',
                    'body_html' => '<p>Contact our team for support.</p>',
                    'status' => 'published',
                    'published_at' => now()->subDays(2),
                ]
            );
        }
    }
}
