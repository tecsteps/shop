<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    /** @use HasFactory<\Database\Factories\CheckoutFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'cart_id',
        'customer_id',
        'status',
        'payment_method',
        'email',
        'shipping_address_json',
        'billing_address_json',
        'shipping_method_id',
        'discount_code',
        'tax_provider_snapshot_json',
        'totals_json',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,
            'payment_method' => PaymentMethod::class,
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
            'tax_provider_snapshot_json' => 'array',
            'totals_json' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the cart backing the checkout.
     *
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the customer that owns the checkout.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Whether any cart line requires physical shipping.
     */
    public function requiresShipping(): bool
    {
        return $this->cart->requiresShipping();
    }

    /**
     * Whether the checkout is expired (either transitioned or past its deadline).
     */
    public function isExpired(): bool
    {
        if ($this->status === CheckoutStatus::Expired) {
            return true;
        }

        if ($this->status === CheckoutStatus::Completed) {
            return false;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
