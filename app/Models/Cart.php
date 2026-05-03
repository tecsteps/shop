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
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'currency',
        'discount_code',
        'cart_version',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
        'discount_code' => null,
        'cart_version' => 1,
        'status' => CartStatus::Active->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<CartLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    /**
     * @return HasMany<Checkout, $this>
     */
    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    public function subtotalAmount(): int
    {
        return (int) $this->lines->sum('line_subtotal_amount');
    }

    public function discountAmount(): int
    {
        return (int) $this->lines->sum('line_discount_amount');
    }

    public function totalAmount(): int
    {
        return (int) $this->lines->sum('line_total_amount');
    }

    public function itemCount(): int
    {
        return (int) $this->lines->sum('quantity');
    }
}
