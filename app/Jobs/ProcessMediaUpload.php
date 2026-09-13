<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Marks uploaded media ready and generates resized versions (spec 05 section 15.2).
 */
class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<string, int>
     */
    protected array $sizes = [
        'thumbnail' => 150,
        'small' => 300,
        'medium' => 600,
        'large' => 1200,
    ];

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = ProductMedia::query()->find($this->mediaId);

        if ($media === null) {
            return;
        }

        try {
            if ($media->type === MediaType::Image) {
                $this->generateSizes($media);
            }

            $media->update(['status' => MediaStatus::Ready]);
        } catch (Throwable $exception) {
            $media->update(['status' => MediaStatus::Failed]);

            report($exception);
        }
    }

    protected function generateSizes(ProductMedia $media): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($media->storage_key)) {
            return;
        }

        $binary = $disk->get($media->storage_key);

        if ($binary === null) {
            return;
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);

            return;
        }

        if ($media->width === null || $media->height === null) {
            $media->update(['width' => $sourceWidth, 'height' => $sourceHeight]);
        }

        $extension = strtolower(pathinfo($media->storage_key, PATHINFO_EXTENSION));

        foreach ($this->sizes as $name => $maxDimension) {
            $ratio = min(1, $maxDimension / max($sourceWidth, $sourceHeight));
            $targetWidth = max(1, (int) round($sourceWidth * $ratio));
            $targetHeight = max(1, (int) round($sourceHeight * $ratio));

            $resized = imagescale($source, $targetWidth, $targetHeight);

            if ($resized === false) {
                continue;
            }

            $this->persistResized($resized, $extension, "media/{$media->product_id}/{$media->getKey()}/{$name}.{$extension}");
            imagedestroy($resized);
        }

        imagedestroy($source);
    }

    protected function persistResized(\GdImage $image, string $extension, string $key): void
    {
        ob_start();

        $written = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, null, 85),
            'png' => imagepng($image, null, 8),
            'webp' => imagewebp($image, null, 85),
            'gif' => imagegif($image),
            default => false,
        };

        $binary = (string) ob_get_clean();

        if ($written && $binary !== '') {
            Storage::disk('public')->put($key, $binary);
        }
    }
}
