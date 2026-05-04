<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
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
        'type' => 'image',
        'position' => 0,
        'status' => 'processing',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('current_store', function (Builder $builder): void {
            if (! app()->bound('current_store')) {
                return;
            }

            $store = app('current_store');

            if (! $store instanceof Store) {
                return;
            }

            $builder->whereHas('product', function (Builder $query) use ($store): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey());
            });
        });

        static::deleted(function (ProductMedia $media): void {
            $disk = Storage::disk('public');

            $disk->delete($media->storage_key);
            $disk->deleteDirectory("media/{$media->product_id}/{$media->getKey()}");
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'width' => 'integer',
            'height' => 'integer',
            'byte_size' => 'integer',
            'position' => 'integer',
            'status' => MediaStatus::class,
        ];
    }
}
