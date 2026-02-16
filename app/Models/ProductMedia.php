<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    /** @use HasFactory<\Database\Factories\ProductMediaFactory> */
    use HasFactory;

    public $timestamps = false;

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

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'status' => MediaStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
