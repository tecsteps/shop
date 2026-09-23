<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'type', 'storage_key', 'alt_text', 'width', 'height', 'mime_type', 'byte_size', 'position', 'status'];

    protected static function booted(): void
    {
        static::deleting(function (ProductMedia $media): void {
            $disk = Storage::disk('public');
            $disk->delete($media->storage_key);
            $disk->deleteDirectory("media/{$media->product_id}/{$media->id}");
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(function (): string {
            if (filter_var($this->storage_key, FILTER_VALIDATE_URL)) {
                return $this->storage_key;
            }

            return Storage::disk('public')->url($this->storage_key);
        });
    }

    protected function alt(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->alt_text);
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->type !== 'image' || $this->status !== 'ready') {
                return $this->url;
            }

            $directory = "media/{$this->product_id}/{$this->id}";
            $extension = strtolower(pathinfo($this->storage_key, PATHINFO_EXTENSION));
            $webp = "{$directory}/thumbnail.webp";
            $originalFormat = "{$directory}/thumbnail.{$extension}";

            if (Storage::disk('public')->exists($webp)) {
                return Storage::disk('public')->url($webp);
            }

            return Storage::disk('public')->exists($originalFormat)
                ? Storage::disk('public')->url($originalFormat)
                : $this->url;
        });
    }
}
