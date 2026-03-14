<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
        'currency',
        'cart_version',
        'status',
    ];

    /**
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
     * @return HasOne<Checkout, $this>
     */
    public function checkout(): HasOne
    {
        return $this->hasOne(Checkout::class);
    }
}
