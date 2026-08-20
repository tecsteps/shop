<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $fillable = ['product_id', 'type', 'path', 'storage_key', 'url', 'alt_text', 'width', 'height', 'mime_type', 'byte_size', 'checksum', 'status', 'position', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
