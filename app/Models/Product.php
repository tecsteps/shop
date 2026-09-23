<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'title', 'handle', 'status', 'description_html', 'vendor', 'product_type', 'tags', 'published_at'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'published_at' => 'datetime'];
    }

    protected function priceAmount(): Attribute
    {
        return Attribute::get(function (): int {
            $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

            return (int) ($variants->min('price_amount') ?? 0);
        });
    }

    protected function compareAtAmount(): Attribute
    {
        return Attribute::get(function (): ?int {
            $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
            $amount = $variants->max('compare_at_amount');

            return $amount === null ? null : (int) $amount;
        });
    }

    protected function currency(): Attribute
    {
        return Attribute::get(function (): string {
            $variant = $this->relationLoaded('variants') ? $this->variants->first() : $this->variants()->first();

            return $variant?->currency ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
        });
    }

    protected function soldOut(): Attribute
    {
        return Attribute::get(function (): bool {
            $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->with('inventoryItem')->get();

            return $variants->isNotEmpty() && $variants->every(fn (ProductVariant $variant): bool => $variant->inventoryItem && $variant->inventoryItem->policy !== 'continue' && $variant->inventoryItem->quantity_on_hand <= $variant->inventoryItem->quantity_reserved);
        });
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->description_html);
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            DB::table('products_fts')->where('product_id', (string) $product->id)->delete();
            DB::table('products_fts')->insert([
                'store_id' => (string) $product->store_id,
                'product_id' => (string) $product->id,
                'title' => $product->title,
                'description' => strip_tags((string) $product->description_html),
                'vendor' => (string) $product->vendor,
                'product_type' => (string) $product->product_type,
                'tags' => implode(' ', $product->tags ?? []),
            ]);
        });

        static::deleted(function (Product $product): void {
            DB::table('products_fts')->where('product_id', (string) $product->id)->delete();
        });
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')->withPivot('position');
    }
}
