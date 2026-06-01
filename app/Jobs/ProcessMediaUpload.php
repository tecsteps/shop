<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Generates resized renditions for an uploaded product image and flips the
 * media record from `processing` to `ready` (or `failed`).
 *
 * Three standard sizes are produced alongside the original, each scaled to fit
 * within a bounding box while preserving aspect ratio:
 *   - thumbnail: 150x150
 *   - medium:    600x600
 *   - large:     1200x1200
 *
 * Resizing uses PHP's bundled GD extension (no third-party image library), so
 * the platform has no extra Composer dependency. Renditions are written next to
 * the original on the same disk, suffixed with the size name.
 */
class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Target bounding boxes for each generated rendition, keyed by size name.
     *
     * @var array<string, int>
     */
    private const SIZES = [
        'thumbnail' => 150,
        'medium' => 600,
        'large' => 1200,
    ];

    public function __construct(
        public int $mediaId,
        public string $disk = 'public',
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $media = ProductMedia::find($this->mediaId);

        if ($media === null) {
            return;
        }

        try {
            $disk = Storage::disk($this->disk);
            $source = $disk->get($media->storage_key);

            if ($source === null) {
                throw new \RuntimeException("Source media missing: {$media->storage_key}");
            }

            $image = @imagecreatefromstring($source);

            if ($image === false) {
                throw new \RuntimeException('Unsupported or corrupt image data.');
            }

            $width = imagesx($image);
            $height = imagesy($image);

            foreach (self::SIZES as $name => $box) {
                $resized = $this->scaleToBox($image, $width, $height, $box);
                $disk->put($this->renditionKey($media->storage_key, $name), $this->encodeJpeg($resized));
                imagedestroy($resized);
            }

            imagedestroy($image);

            $media->update([
                'width' => $width,
                'height' => $height,
                'status' => MediaStatus::Ready->value,
            ]);
        } catch (Throwable $e) {
            $media->update(['status' => MediaStatus::Failed->value]);

            report($e);
        }
    }

    /**
     * Mark the media as failed if the job exhausts its attempts.
     */
    public function failed(?Throwable $exception): void
    {
        ProductMedia::whereKey($this->mediaId)->update(['status' => MediaStatus::Failed->value]);
    }

    /**
     * Scale a GD image to fit within a square bounding box, preserving aspect
     * ratio. Never upscales beyond the original dimensions.
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function scaleToBox($image, int $width, int $height, int $box)
    {
        $ratio = min($box / $width, $box / $height, 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    /**
     * Encode a GD image as JPEG bytes.
     *
     * @param  \GdImage  $image
     */
    private function encodeJpeg($image): string
    {
        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    /**
     * Build the storage key for a rendition, e.g. "products/x.jpg" ->
     * "products/x-thumbnail.jpg".
     */
    private function renditionKey(string $key, string $size): string
    {
        $extension = pathinfo($key, PATHINFO_EXTENSION);
        $base = $extension !== '' ? substr($key, 0, -(strlen($extension) + 1)) : $key;

        return "{$base}-{$size}.jpg";
    }
}
