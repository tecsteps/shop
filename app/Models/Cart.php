<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $customer_id
 * @property string|null $session_id
 * @property CartStatus $status
 * @property string $currency
 * @property int $cart_version
 * @property string|null $discount_code
 * @property string|null $note
 * @property array<string, mixed>|null $metadata
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CartLine> $lines
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Checkout> $checkouts
 */
class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
        'session_id',
        'status',
        'currency',
        'cart_version',
        'discount_code',
        'note',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
            'cart_version' => 'integer',
            'metadata' => 'array',
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
     * @return HasMany<Checkout, $this>
     */
    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    public function incrementVersion(): void
    {
        $this->increment('cart_version');
        $this->refresh();
    }
}
