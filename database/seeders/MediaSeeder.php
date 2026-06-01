<?php

namespace Database\Seeders;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates lightweight, self-contained placeholder product images so the demo
 * store renders real imagery (no broken-image icons) in both the storefront and
 * the admin.
 *
 * Each active product without media gets a branded PNG (a diagonal two-tone
 * gradient derived deterministically from the product title, with the product's
 * initials centred) written to the `public` disk, plus the three renditions
 * (thumbnail/medium/large) the catalog pipeline expects, and a `ready`
 * {@see \App\Models\ProductMedia} row. No external assets, fonts, or network are
 * required — everything is drawn with GD's built-in fonts.
 *
 * Idempotent: products that already have media are skipped, so re-running
 * `migrate:fresh --seed` (or a plain re-seed) never duplicates images.
 */
class MediaSeeder extends Seeder
{
    /**
     * Storage directory (relative to the public disk root) for seeded images.
     */
    private const DIR = 'products/seed';

    /**
     * Rendition sizes the catalog pipeline references, keyed by suffix.
     *
     * @var array<string, int>
     */
    private const RENDITIONS = ['thumbnail' => 150, 'medium' => 600, 'large' => 1200];

    /**
     * Curated background palettes (two-tone gradients), picked deterministically
     * per product so the grid looks varied but stable across re-seeds.
     *
     * @var list<array{0: array{0:int,1:int,2:int}, 1: array{0:int,1:int,2:int}}>
     */
    private const PALETTES = [
        [[37, 99, 235], [29, 78, 216]],     // blue
        [[15, 118, 110], [13, 148, 136]],   // teal
        [[219, 39, 119], [157, 23, 77]],    // pink
        [[217, 119, 6], [180, 83, 9]],      // amber
        [[124, 58, 237], [109, 40, 217]],   // violet
        [[5, 150, 105], [4, 120, 87]],      // emerald
        [[71, 85, 105], [30, 41, 59]],      // slate
        [[225, 29, 72], [159, 18, 57]],     // rose
    ];

    public function run(): void
    {
        if (! \extension_loaded('gd')) {
            $this->command?->warn('GD extension not loaded; skipping product image generation.');

            return;
        }

        $store = (new DemoStoreSeeder)->store();
        app()->instance('current_store', $store);

        $disk = Storage::disk('public');
        $disk->makeDirectory(self::DIR);

        Product::query()
            ->where('store_id', $store->id)
            ->whereDoesntHave('media')
            ->orderBy('id')
            ->each(fn (Product $product) => $this->seedImageFor($product));
    }

    private function seedImageFor(Product $product): void
    {
        $slug = Str::slug($product->title);
        $key = self::DIR.'/'.$slug.'.jpg';
        $palette = self::PALETTES[crc32($product->title) % count(self::PALETTES)];

        $disk = Storage::disk('public');

        // Source (large) + each rendition.
        $disk->put($key, $this->renderJpeg($product->title, $palette, 1200));

        foreach (self::RENDITIONS as $suffix => $size) {
            $disk->put($this->renditionKey($key, $suffix), $this->renderJpeg($product->title, $palette, $size));
        }

        $product->media()->create([
            'type' => MediaType::Image->value,
            'storage_key' => $key,
            'alt_text' => $product->title,
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => (int) $disk->size($key),
            'position' => 0,
            'status' => MediaStatus::Ready->value,
        ]);
    }

    /**
     * Render a square branded placeholder as JPEG bytes: a diagonal gradient
     * between the palette's two colours, with the product initials centred.
     *
     * @param  array{0: array{0:int,1:int,2:int}, 1: array{0:int,1:int,2:int}}  $palette
     */
    private function renderJpeg(string $title, array $palette, int $size): string
    {
        [$from, $to] = $palette;

        // Render a small gradient (one filled rect per diagonal band) then
        // upscale to the target size. Banding the gradient on a tiny canvas and
        // resampling is dramatically faster than per-pixel writes at 1200px.
        $base = 64;
        $small = imagecreatetruecolor($base, $base);

        for ($i = 0; $i < $base * 2; $i++) {
            $t = $i / ($base * 2);
            $r = (int) round($from[0] + ($to[0] - $from[0]) * $t);
            $g = (int) round($from[1] + ($to[1] - $from[1]) * $t);
            $b = (int) round($from[2] + ($to[2] - $from[2]) * $t);
            $color = imagecolorallocate($small, $r, $g, $b);
            // Anti-diagonal band: pixels where x + y == i.
            imageline($small, 0, $i, $i, 0, $color);
        }

        $image = imagecreatetruecolor($size, $size);
        imagecopyresampled($image, $small, 0, 0, 0, 0, $size, $size, $base, $base);
        imagedestroy($small);

        $this->drawInitials($image, $title, $size);

        ob_start();
        imagejpeg($image, null, 82);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    /**
     * Draw the product's initials (up to 3 letters) centred, using GD's largest
     * built-in font scaled up via a transparent overlay.
     */
    private function drawInitials(\GdImage $image, string $title, int $size): void
    {
        $initials = Str::of($title)
            ->explode(' ')
            ->filter()
            ->take(3)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        if ($initials === '') {
            $initials = '·';
        }

        $font = 5; // GD's largest built-in font.
        $charW = imagefontwidth($font);
        $charH = imagefontheight($font);
        $textW = $charW * strlen($initials);

        // Draw the initials once at native bitmap size onto a tight transparent
        // canvas, then resample that single bitmap up to a target glyph height of
        // ~26% of the image. A single smooth resample keeps the letters readable
        // and centred without per-character drift, and needs no font files (so
        // it is fully portable across environments/CI).
        $small = imagecreatetruecolor($textW, $charH);
        imagesavealpha($small, true);
        imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
        imagestring($small, $font, 0, 0, $initials, imagecolorallocate($small, 255, 255, 255));

        $targetH = (int) round($size * 0.26);
        $targetW = (int) round($targetH * ($textW / $charH));
        $dstX = (int) (($size - $targetW) / 2);
        $dstY = (int) (($size - $targetH) / 2);
        imagecopyresampled($image, $small, $dstX, $dstY, 0, 0, $targetW, $targetH, $textW, $charH);
        imagedestroy($small);
    }

    /**
     * Build a rendition key, e.g. "products/seed/x.jpg" ->
     * "products/seed/x-thumbnail.jpg" (mirrors {@see \App\Jobs\ProcessMediaUpload}).
     */
    private function renditionKey(string $key, string $suffix): string
    {
        $extension = pathinfo($key, PATHINFO_EXTENSION);
        $base = $extension !== '' ? substr($key, 0, -(strlen($extension) + 1)) : $key;

        return "{$base}-{$suffix}.jpg";
    }
}
