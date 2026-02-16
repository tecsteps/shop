<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    /** @use HasFactory<\Database\Factories\CheckoutFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'cart_id',
        'customer_id',
        'email',
        'shipping_address_json',
        'billing_address_json',
        'shipping_rate_id',
        'shipping_method_name',
        'shipping_amount',
        'discount_code',
        'discount_amount',
        'payment_method',
        'status',
        'totals_json',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
            'totals_json' => 'array',
            'shipping_amount' => 'integer',
            'discount_amount' => 'integer',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
