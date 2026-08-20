<?php

namespace App\Jobs;

use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public ProductMedia $media) {}

    public function handle(): void
    {
        try {
            $this->media->update(['status' => 'ready']);
        } catch (Throwable $exception) {
            $this->media->update(['status' => 'failed', 'metadata' => ['error' => $exception->getMessage()]]);
            throw $exception;
        }
    }
}
