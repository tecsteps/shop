<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
            'id' => 'integer',
            'store_id' => 'integer',
            'cart_id' => 'integer',
            'customer_id' => 'integer',
            'status' => CheckoutStatus::class,
            'payment_method' => PaymentMethod::class,
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
            'shipping_method_id' => 'integer',
            'tax_provider_snapshot_json' => 'array',
            'totals_json' => 'array',
            'expires_at' => 'datetime',
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
