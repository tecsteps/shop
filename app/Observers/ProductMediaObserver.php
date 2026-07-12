<?php

namespace App\Observers;

use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

final class ProductMediaObserver
{
    public function deleted(ProductMedia $media): void
    {
        $disk = Storage::disk('public');
        $disk->delete($media->storage_key);
        $disk->deleteDirectory("media/{$media->product_id}/{$media->id}");
    }
}
