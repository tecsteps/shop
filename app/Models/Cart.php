<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Models\Concerns\BelongsToStore;
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
            'status' => CartStatus::class,
            'cart_version' => 'integer',
        ];
    }

    /**
     * Get the customer that owns the cart.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the lines in the cart.
     *
     * @return HasMany<CartLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    /**
     * Get the checkouts created from the cart.
     *
     * @return HasMany<Checkout, $this>
     */
    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    /**
     * Sum of all line subtotals (before discounts), in minor units.
     */
    public function subtotal(): int
    {
        return (int) $this->lines->sum('line_subtotal_amount');
    }

    /**
     * Total number of units across all lines.
     */
    public function itemCount(): int
    {
        return (int) $this->lines->sum('quantity');
    }

    /**
     * Number of distinct lines in the cart.
     */
    public function lineCount(): int
    {
        return $this->lines->count();
    }

    /**
     * Find a line by variant ID.
     */
    public function findLineByVariant(int $variantId): ?CartLine
    {
        return $this->lines->firstWhere('variant_id', $variantId);
    }

    /**
     * Recalculate subtotal/total amounts on every line.
     */
    public function recalculateLines(): void
    {
        $this->lines->each->recalculate();
    }

    /**
     * Whether any line in the cart requires physical shipping.
     */
    public function requiresShipping(): bool
    {
        return $this->lines->contains(
            fn (CartLine $line): bool => (bool) $line->variant?->requires_shipping
        );
    }
}
