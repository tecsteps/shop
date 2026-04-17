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

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string, array{width: int, height: int}> */
    private const SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
    ];

    public function __construct(
        public ProductMedia $media
    ) {}

    public function handle(): void
    {
        try {
            $disk = Storage::disk('public');
            $path = $this->media->storage_key;

            if (! $disk->exists($path)) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $fullPath = $disk->path($path);
            $imageInfo = @getimagesize($fullPath);

            if ($imageInfo === false) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $this->media->update([
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'mime_type' => $imageInfo['mime'],
                'byte_size' => $disk->size($path),
                'status' => MediaStatus::Ready,
            ]);
        } catch (\Throwable) {
            $this->media->update(['status' => MediaStatus::Failed]);
        }
    }
}
