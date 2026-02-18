<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property MediaType $type
 * @property string $storage_key
 * @property string|null $alt_text
 * @property int|null $width
 * @property int|null $height
 * @property string|null $mime_type
 * @property int|null $byte_size
 * @property int $position
 * @property MediaStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class ProductMedia extends Model
{
    /** @use HasFactory<\Database\Factories\ProductMediaFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'product_media';

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'status' => MediaStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
