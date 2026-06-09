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

    /**
     * The product_media table has no updated_at column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The table associated with the model.
     */
    protected $table = 'product_media';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'status' => MediaStatus::class,
            'width' => 'integer',
            'height' => 'integer',
            'byte_size' => 'integer',
            'position' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Storage key for a derived (resized) version of this media file, e.g.
     * "media/products/1/photo.jpg" with size "thumbnail" becomes
     * "media/products/1/photo_thumbnail.jpg".
     */
    public function derivedStorageKey(string $size): string
    {
        $directory = pathinfo($this->storage_key, PATHINFO_DIRNAME);
        $filename = pathinfo($this->storage_key, PATHINFO_FILENAME);
        $extension = pathinfo($this->storage_key, PATHINFO_EXTENSION);

        $prefix = $directory === '.' ? '' : "{$directory}/";
        $suffix = $extension === '' ? '' : ".{$extension}";

        return "{$prefix}{$filename}_{$size}{$suffix}";
    }
}
