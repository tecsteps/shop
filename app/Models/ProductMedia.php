<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    protected $fillable = ['product_id', 'type', 'path', 'storage_key', 'url', 'alt_text', 'width', 'height', 'mime_type', 'byte_size', 'checksum', 'status', 'position', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(function (ProductMedia $media): void {
            $keys = array_values(array_filter([$media->storage_key, $media->path, ...array_values($media->metadata['variants'] ?? [])]));
            $disk = Storage::disk('public');

            foreach (array_unique($keys) as $key) {
                $disk->delete($key);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
