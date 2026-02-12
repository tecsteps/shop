<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $mediaId,
    ) {}

    public function handle(): void
    {
        $media = ProductMedia::find($this->mediaId);

        if (! $media) {
            return;
        }

        try {
            $media->update(['status' => MediaStatus::Ready]);
        } catch (\Throwable $e) {
            Log::error('Media processing failed', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            $media->update(['status' => MediaStatus::Failed]);
        }
    }
}
