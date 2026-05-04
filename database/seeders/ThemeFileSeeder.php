<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\ThemeFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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
                $contents = $this->contents($theme, $path);
                $storageKey = "themes/{$theme->getKey()}/{$path}";

                Storage::disk('local')->put($storageKey, $contents);

                ThemeFile::withoutGlobalScopes()->updateOrCreate(
                    [
                        'theme_id' => $theme->getKey(),
                        'path' => $path,
                    ],
                    [
                        'storage_key' => $storageKey,
                        'sha256' => hash('sha256', $contents),
                        'byte_size' => strlen($contents),
                    ],
                );
            }
        });
    }

    private function contents(Theme $theme, string $path): string
    {
        return match ($path) {
            'layouts/storefront.blade.php' => "<x-layouts.storefront :title=\"\$title ?? '{$theme->name}'\">\n    {{ \$slot }}\n</x-layouts.storefront>\n",
            'sections/hero.blade.php' => "<section class=\"hero\">\n    <h1>{{ data_get(\$settings, 'home.hero.heading') }}</h1>\n</section>\n",
            'sections/featured-products.blade.php' => "<section class=\"featured-products\">\n    @foreach(\$products as \$product)\n        <article>{{ \$product->title }}</article>\n    @endforeach\n</section>\n",
            default => '',
        };
    }
}
