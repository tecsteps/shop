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
        public readonly ProductMedia $media,
    ) {}

    public function handle(): void
    {
        $this->media->update(['status' => MediaStatus::Ready]);
    }
}
