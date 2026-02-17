<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    /**
     * @var array<int, array{name: string, width: int, height: int}>
     */
    private const array SIZES = [
        ['name' => 'thumbnail', 'width' => 150, 'height' => 150],
        ['name' => 'medium', 'width' => 600, 'height' => 600],
        ['name' => 'large', 'width' => 1200, 'height' => 1200],
    ];

    public function __construct(
        public ProductMedia $media,
    ) {}

    public function handle(): void
    {
        try {
            $disk = Storage::disk('public');
            $originalPath = $this->media->storage_key;

            if (! $disk->exists($originalPath)) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $fullPath = $disk->path($originalPath);
            $pathInfo = pathinfo($originalPath);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = $pathInfo['extension'] ?? 'jpg';

            $source = $this->createImageFromFile($fullPath, $this->media->mime_type);

            if ($source === false) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            foreach (self::SIZES as $size) {
                $resized = $this->resizeImage($source, $size['width'], $size['height']);
                $resizedPath = $directory.'/'.$filename.'_'.$size['name'].'.'.$extension;
                $tempFile = tempnam(sys_get_temp_dir(), 'media_');

                imagejpeg($resized, $tempFile, 85);
                imagedestroy($resized);

                $disk->put($resizedPath, file_get_contents($tempFile));
                unlink($tempFile);
            }

            imagedestroy($source);

            $this->media->update([
                'status' => MediaStatus::Ready,
            ]);
        } catch (\Throwable $e) {
            Log::error('Media processing failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
            ]);

            $this->media->update([
                'status' => MediaStatus::Failed,
            ]);
        }
    }

    private function createImageFromFile(string $path, ?string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    private function resizeImage(\GdImage $source, int $targetWidth, int $targetHeight): \GdImage
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($srcRatio > $targetRatio) {
            $cropHeight = $srcHeight;
            $cropWidth = (int) ($srcHeight * $targetRatio);
        } else {
            $cropWidth = $srcWidth;
            $cropHeight = (int) ($srcWidth / $targetRatio);
        }

        $cropX = (int) (($srcWidth - $cropWidth) / 2);
        $cropY = (int) (($srcHeight - $cropHeight) / 2);

        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($dest, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $dest;
    }
}
