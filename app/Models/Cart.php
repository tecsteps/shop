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
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\CartFactory> */
    use HasFactory;

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
     * The customer who owns this cart (null for a guest cart).
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The line items in this cart.
     *
     * @return HasMany<CartLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    /**
     * The checkout sessions started from this cart.
     *
     * @return HasMany<Checkout, $this>
     */
    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    /**
     * The sum of all line subtotals in minor units (cents).
     */
    public function subtotalAmount(): int
    {
        return (int) $this->lines->sum('line_subtotal_amount');
    }
}
