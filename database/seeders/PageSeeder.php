<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        Page::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'about',
            ],
            [
                'title' => 'About Acme',
                'body_html' => '<p>Acme Fashion designs practical essentials for daily wear.</p>',
                'status' => PageStatus::Published,
                'published_at' => now(),
            ],
        );
    }
}
