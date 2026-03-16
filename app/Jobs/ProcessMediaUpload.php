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

    public function __construct(
        public int $mediaId
    ) {}

    public function handle(): void
    {
        $media = ProductMedia::query()->find($this->mediaId);

        if (! $media) {
            return;
        }

        try {
            $disk = Storage::disk('public');
            $path = $media->storage_key;

            if (! $disk->exists($path)) {
                $media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $fileContents = $disk->get($path);
            $imageInfo = getimagesizefromstring($fileContents);

            if ($imageInfo !== false) {
                $media->update([
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'mime_type' => $imageInfo['mime'],
                    'byte_size' => strlen($fileContents),
                    'status' => MediaStatus::Ready,
                ]);
            } else {
                $media->update([
                    'byte_size' => strlen($fileContents),
                    'status' => MediaStatus::Ready,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Media processing failed', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            $media->update(['status' => MediaStatus::Failed]);
        }
    }
}
