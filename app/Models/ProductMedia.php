<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Database\Factories\ProductMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    /** @use HasFactory<ProductMediaFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'type',
        'storage_key',
        'alt_text',
        'width',
        'height',
        'mime_type',
        'byte_size',
        'position',
        'status',
    ];

    protected $attributes = [
        'type' => MediaType::Image->value,
        'position' => 0,
        'status' => MediaStatus::Processing->value,
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'width' => 'integer',
            'height' => 'integer',
            'byte_size' => 'integer',
            'position' => 'integer',
            'status' => MediaStatus::class,
            'created_at' => 'datetime',
        ];
    }
}
