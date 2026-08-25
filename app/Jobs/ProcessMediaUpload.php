<?php

namespace App\Jobs;

use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = ProductMedia::find($this->mediaId);

        if (! $media) {
            return;
        }

        try {
            if (Storage::disk('public')->exists($media->storage_key)) {
                $media->update(['status' => 'ready']);
            }
        } catch (\Throwable $e) {
            $media->update(['status' => 'failed']);
        }
    }
}
