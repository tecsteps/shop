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

    /**
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
            'totals_json' => 'array',
            'tax_provider_snapshot_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<ShippingRate, $this>
     */
    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class, 'shipping_method_id');
    }
}
