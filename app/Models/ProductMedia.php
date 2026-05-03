<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    /** @use HasFactory<\Database\Factories\ProductMediaFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var array<string, array{width: int, height: int}>
     */
    public const ImageVariantSizes = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
    ];

    /**
     * @var list<string>
     */
    public const ImageVariantExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => MediaType::Image->value,
        'position' => 0,
        'status' => MediaStatus::Processing->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'status' => MediaStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ProductMedia $media): void {
            Storage::disk('public')->delete([
                $media->storage_key,
                ...$media->imageVariantStorageKeys(),
            ]);
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function imageVariantStorageKey(string $size, string $extension): string
    {
        return 'media/'.$this->product_id.'/'.$this->id.'/'.$size.'.'.ltrim($extension, '.');
    }

    /**
     * @return list<string>
     */
    public function imageVariantStorageKeys(): array
    {
        $keys = [];

        foreach (array_keys(self::ImageVariantSizes) as $size) {
            foreach (self::ImageVariantExtensions as $extension) {
                $keys[] = $this->imageVariantStorageKey($size, $extension);
            }
        }

        return $keys;
    }
}
