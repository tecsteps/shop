<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProductMedia $media,
    ) {}

    public function handle(): void
    {
        // In a real application, this would resize images to:
        // - thumbnail: 150x150
        // - medium: 600x600
        // - large: 1200x1200
        // For now, just mark the media as ready.
        $this->media->update([
            'status' => MediaStatus::Ready,
        ]);
    }
}
