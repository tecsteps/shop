<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Derived image sizes: name => maximum dimension in pixels.
     *
     * @var array<string, int>
     */
    public const array SIZES = [
        'thumbnail' => 150,
        'medium' => 600,
        'large' => 1200,
    ];

    public function __construct(public ProductMedia $media) {}

    /**
     * Resize the original upload into the standard derived sizes using GD and
     * flip the media status from processing to ready (or failed on error).
     */
    public function handle(): void
    {
        try {
            $this->process();

            $this->media->update(['status' => MediaStatus::Ready]);
        } catch (Throwable $exception) {
            $this->media->update(['status' => MediaStatus::Failed]);

            report($exception);
        }
    }

    private function process(): void
    {
        $disk = Storage::disk('public');

        $contents = $disk->get($this->media->storage_key);

        if ($contents === null) {
            throw new RuntimeException("Original media file [{$this->media->storage_key}] not found.");
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException("Could not decode image [{$this->media->storage_key}].");
        }

        $width = imagesx($source);
        $height = imagesy($source);

        foreach (self::SIZES as $size => $maxDimension) {
            $resized = $this->resizeToFit($source, $width, $height, $maxDimension);

            $disk->put(
                $this->media->derivedStorageKey($size),
                $this->encode($resized),
            );

            imagedestroy($resized);
        }

        imagedestroy($source);

        $this->media->forceFill([
            'width' => $width,
            'height' => $height,
        ])->save();
    }

    /**
     * Scale the image so its longest edge fits within the given dimension,
     * preserving aspect ratio. Images smaller than the target are kept as-is.
     */
    private function resizeToFit(GdImage $source, int $width, int $height, int $maxDimension): GdImage
    {
        $scale = min(1.0, $maxDimension / max($width, $height));

        $targetWidth = max(1, (int) round($width * $scale));

        $resized = imagescale($source, $targetWidth, -1, IMG_BICUBIC);

        if ($resized === false) {
            throw new RuntimeException('Failed to resize image.');
        }

        return $resized;
    }

    private function encode(GdImage $image): string
    {
        $extension = strtolower(pathinfo($this->media->storage_key, PATHINFO_EXTENSION));

        ob_start();

        $encoded = match ($extension) {
            'png' => imagepng($image),
            'gif' => imagegif($image),
            'webp' => imagewebp($image),
            default => imagejpeg($image, null, 85),
        };

        $contents = ob_get_clean();

        if ($encoded === false || $contents === false) {
            throw new RuntimeException('Failed to encode resized image.');
        }

        return $contents;
    }
}
