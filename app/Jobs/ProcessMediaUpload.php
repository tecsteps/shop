<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductMedia $media) {}

    public function handle(): void
    {
        Log::info('ProcessMediaUpload stub: would resize and transcode media', [
            'media_id' => $this->media->id,
            'storage_key' => $this->media->storage_key,
        ]);

        $this->media->status = MediaStatus::Ready;
        $this->media->save();
    }
}
