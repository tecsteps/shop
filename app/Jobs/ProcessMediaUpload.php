<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public ProductMedia $media) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($this->media->storage_key)) {
                $this->media->forceFill(['status' => MediaStatus::Failed])->save();

                return;
            }

            $path = $disk->path($this->media->storage_key);
            $metadata = [
                'byte_size' => $disk->size($this->media->storage_key),
                'status' => MediaStatus::Ready,
            ];

            if ($this->media->type === MediaType::Image && ($size = @getimagesize($path))) {
                $metadata['width'] = $size[0];
                $metadata['height'] = $size[1];
                $metadata['mime_type'] = $size['mime'] ?? $this->media->mime_type;
            }

            $this->media->forceFill($metadata)->save();
        } catch (Throwable) {
            $this->media->forceFill(['status' => MediaStatus::Failed])->save();
        }
    }
}
