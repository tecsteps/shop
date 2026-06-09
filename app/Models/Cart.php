<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'currency',
        'cart_version',
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
            'cart_version' => 'integer',
            'status' => CartStatus::class,
        ];
    }

    /**
     * Scope the query to active carts.
     *
     * @param  Builder<Cart>  $query
     * @return Builder<Cart>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CartStatus::Active);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    /**
     * Sum of all line subtotals in minor units.
     */
    public function subtotalAmount(): int
    {
        return (int) $this->lines()->sum('line_subtotal_amount');
    }

    /**
     * Total number of units across all lines.
     */
    public function itemCount(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    /**
     * Whether any line's variant requires physical shipping.
     */
    public function requiresShipping(): bool
    {
        return $this->lines()
            ->whereHas('variant', fn (Builder $query) => $query->where('requires_shipping', true))
            ->exists();
    }

    /**
     * Total shippable weight in grams across all lines.
     */
    public function totalWeightGrams(): int
    {
        return $this->lines()
            ->with('variant')
            ->get()
            ->filter(fn (CartLine $line): bool => (bool) $line->variant?->requires_shipping)
            ->sum(fn (CartLine $line): int => (int) ($line->variant->weight_g ?? 0) * $line->quantity);
    }
}
