<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    use BelongsToStore, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'cart_id',
        'customer_id',
        'status',
        'email',
        'shipping_address_json',
        'billing_address_json',
        'selected_shipping_rate_id',
        'shipping_method_name',
        'shipping_amount',
        'payment_method',
        'discount_code',
        'discount_amount',
        'totals_json',
        'expires_at',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
            'totals_json' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'shipping_amount' => 'integer',
            'discount_amount' => 'integer',
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
