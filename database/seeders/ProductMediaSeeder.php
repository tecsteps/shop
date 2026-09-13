<?php

namespace Database\Seeders;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductMediaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Product::query()->orderBy('id')->get() as $product) {
            $this->seedPlaceholder($product);
        }
    }

    protected function seedPlaceholder(Product $product): void
    {
        $storageKey = "products/placeholders/{$product->handle}.svg";

        if (! Storage::disk('public')->exists($storageKey)) {
            Storage::disk('public')->put($storageKey, $this->svgFor($product->title));
        }

        $byteSize = Storage::disk('public')->size($storageKey) ?: strlen($this->svgFor($product->title));

        ProductMedia::query()->updateOrCreate(
            ['product_id' => $product->getKey(), 'storage_key' => $storageKey],
            [
                'type' => MediaType::Image,
                'alt_text' => $product->title,
                'width' => 800,
                'height' => 800,
                'mime_type' => 'image/svg+xml',
                'byte_size' => $byteSize,
                'position' => 0,
                'status' => MediaStatus::Ready,
            ]
        );
    }

    protected function svgFor(string $title): string
    {
        $hue = abs(crc32($title)) % 360;
        $escaped = htmlspecialchars(mb_substr($title, 0, 32), ENT_QUOTES);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" role="img" aria-label="{$escaped}">
<rect width="800" height="800" fill="hsl({$hue}, 28%, 88%)"/>
<rect x="240" y="300" width="320" height="200" rx="16" fill="hsl({$hue}, 32%, 72%)"/>
<text x="400" y="420" font-family="system-ui, sans-serif" font-size="28" text-anchor="middle" fill="hsl({$hue}, 30%, 30%)">{$escaped}</text>
</svg>
SVG;
    }
}
