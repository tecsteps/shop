<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'cart_id', 'customer_id', 'status', 'email', 'payment_method', 'shipping_address_json', 'billing_address_json', 'shipping_rate_id', 'shipping_method_id', 'discount_code', 'totals_json', 'tax_provider_snapshot_json', 'expires_at'];

    protected function casts(): array
    {
        return ['status' => CheckoutStatus::class, 'shipping_address_json' => 'array', 'billing_address_json' => 'array', 'totals_json' => 'array', 'tax_provider_snapshot_json' => 'array', 'expires_at' => 'datetime'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
