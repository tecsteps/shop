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
        return Attribute::get(fn (): string => $this->url);
    }
}
