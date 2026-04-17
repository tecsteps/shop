<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = ProductMedia::query()->find($this->mediaId);

        if ($media === null) {
            return;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($media->storage_key)) {
                $media->update(['status' => MediaStatus::Failed->value]);

                return;
            }

            $contents = $disk->get($media->storage_key);
            $mimeType = $disk->mimeType($media->storage_key) ?: $media->mime_type;
            $byteSize = $disk->size($media->storage_key);

            $width = $media->width;
            $height = $media->height;

            if ($contents !== null && function_exists('getimagesizefromstring')) {
                $info = @getimagesizefromstring($contents);

                if (is_array($info)) {
                    $width = $info[0] ?? $width;
                    $height = $info[1] ?? $height;
                    $mimeType = $info['mime'] ?? $mimeType;
                }
            }

            $media->update([
                'width' => $width,
                'height' => $height,
                'mime_type' => $mimeType,
                'byte_size' => $byteSize,
                'status' => MediaStatus::Ready->value,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProcessMediaUpload failed', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);
            $media->update(['status' => MediaStatus::Failed->value]);
        }
    }
}
