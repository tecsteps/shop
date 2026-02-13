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

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $mediaId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $media = ProductMedia::query()->find($this->mediaId);

        if (! $media) {
            Log::warning("ProcessMediaUpload: Media record {$this->mediaId} not found.");

            return;
        }

        if ($media->status !== MediaStatus::Processing) {
            return;
        }

        try {
            // For now, just update status to ready.
            // Future: resize images to thumbnail (150x150), small (300x300),
            // medium (600x600), and large (1200x1200).
            $media->update([
                'status' => MediaStatus::Ready,
            ]);
        } catch (\Throwable $e) {
            Log::error("ProcessMediaUpload failed for media {$this->mediaId}: {$e->getMessage()}");

            $media->update([
                'status' => MediaStatus::Failed,
            ]);
        }
    }
}
