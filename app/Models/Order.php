<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'checkout_id', 'customer_id', 'order_number', 'payment_method', 'status', 'financial_status', 'fulfillment_status', 'currency', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'email', 'billing_address_json', 'shipping_address_json', 'placed_at'];

    protected function casts(): array
    {
        return ['billing_address_json' => 'array', 'shipping_address_json' => 'array', 'placed_at' => 'datetime'];
    }

    protected function shippingAddress(): Attribute
    {
        return Attribute::get(fn (): array => $this->shipping_address_json ?? []);
    }

    protected function billingAddress(): Attribute
    {
        return Attribute::get(fn (): array => $this->billing_address_json ?? []);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
