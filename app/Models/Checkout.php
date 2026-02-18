<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $store_id
 * @property int $cart_id
 * @property int|null $customer_id
 * @property string|null $email
 * @property CheckoutStatus $status
 * @property array<string, mixed>|null $shipping_address_json
 * @property array<string, mixed>|null $billing_address_json
 * @property int|null $shipping_method_id
 * @property string|null $payment_method
 * @property array<string, mixed>|null $totals_json
 * @property string|null $discount_code
 * @property array<string, mixed>|null $tax_provider_snapshot_json
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property array<string, mixed>|null $metadata
 */
class Checkout extends Model
{
    /** @use HasFactory<\Database\Factories\CheckoutFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'cart_id',
        'customer_id',
        'email',
        'status',
        'shipping_address_json',
        'billing_address_json',
        'shipping_method_id',
        'payment_method',
        'totals_json',
        'discount_code',
        'tax_provider_snapshot_json',
        'note',
        'expires_at',
        'completed_at',
        'metadata',
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
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
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
