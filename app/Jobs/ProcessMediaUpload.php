<?php

namespace App\Jobs;

use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = ProductMedia::query()->findOrFail($this->mediaId);

        if ($media->type !== 'image' || $media->status === 'ready') {
            return;
        }

        $disk = Storage::disk('public');
        $original = $disk->get($media->storage_key);
        $image = new \Imagick;

        try {
            if (! $image->readImageBlob($original)) {
                throw new RuntimeException('The uploaded image could not be decoded.');
            }

            if ($image->getNumberImages() > 1) {
                $image->setIteratorIndex(0);
                $firstFrame = $image->getImage();
                $image->clear();
                $image->destroy();
                $image = $firstFrame;
            }

            if (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            }

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            if ($width < 1 || $height < 1) {
                throw new RuntimeException('The uploaded image has invalid dimensions.');
            }

            $extension = strtolower(pathinfo($media->storage_key, PATHINFO_EXTENSION));
            $format = $extension === 'jpg' ? 'jpeg' : $extension;
            $directory = "media/{$media->product_id}/{$media->id}";
            $sizes = ['thumbnail' => 150, 'small' => 300, 'medium' => 600, 'large' => 1200];

            foreach ($sizes as $size => $maxDimension) {
                $sized = clone $image;

                try {
                    $sized->setImagePage(0, 0, 0, 0);

                    if ($sized->getImageWidth() > $maxDimension || $sized->getImageHeight() > $maxDimension) {
                        $sized->thumbnailImage($maxDimension, $maxDimension, true, false);
                    }

                    $sized->setImageFormat($format);
                    $sized->setImageCompressionQuality(84);

                    if (! $disk->put("{$directory}/{$size}.{$extension}", $sized->getImageBlob())) {
                        throw new RuntimeException("Could not store the {$size} image derivative.");
                    }

                    if (\Imagick::queryFormats('WEBP') !== []) {
                        $webp = clone $sized;

                        try {
                            $webp->setImageFormat('webp');
                            $webp->setImageCompressionQuality(82);

                            if (! $disk->put("{$directory}/{$size}.webp", $webp->getImageBlob())) {
                                throw new RuntimeException("Could not store the {$size} WebP derivative.");
                            }
                        } finally {
                            $webp->clear();
                            $webp->destroy();
                        }
                    }
                } finally {
                    $sized->clear();
                    $sized->destroy();
                }
            }

            $media->forceFill(['width' => $width, 'height' => $height, 'status' => 'ready'])->save();
        } catch (Throwable $exception) {
            if ($this->attempts() >= $this->tries) {
                $this->markFailed($media, $exception);
            }

            throw $exception;
        } finally {
            $image->clear();
            $image->destroy();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $media = ProductMedia::query()->find($this->mediaId);

        if ($media !== null) {
            $this->markFailed($media, $exception ?? new RuntimeException('Image processing failed.'));
        }
    }

    private function markFailed(ProductMedia $media, Throwable $exception): void
    {
        $media->forceFill(['status' => 'failed'])->save();
        Log::channel('daily')->error('Product media processing failed.', [
            'media_id' => $media->id,
            'product_id' => $media->product_id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
