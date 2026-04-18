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
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ProductMedia $media) {}

    public function handle(): void
    {
        try {
            if (! Storage::disk('public')->exists($this->media->storage_key)) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $this->media->update(['status' => MediaStatus::Ready]);
        } catch (Throwable $e) {
            $this->media->update(['status' => MediaStatus::Failed]);

            throw $e;
        }
    }
}
