<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\ThemeFile;
use Illuminate\Database\Seeder;

class ThemeFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Theme::withoutGlobalScopes()->get()->each(function (Theme $theme): void {
            foreach ([
                'layouts/storefront.blade.php',
                'sections/hero.blade.php',
                'sections/featured-products.blade.php',
            ] as $path) {
                ThemeFile::withoutGlobalScopes()->updateOrCreate(
                    [
                        'theme_id' => $theme->getKey(),
                        'path' => $path,
                    ],
                    [
                        'storage_key' => "themes/{$theme->getKey()}/{$path}",
                        'sha256' => hash('sha256', "{$theme->getKey()}:{$path}"),
                        'byte_size' => 1024,
                    ],
                );
            }
        });
    }
}
