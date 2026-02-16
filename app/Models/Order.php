<?php

namespace App\Models;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
        'order_number',
        'email',
        'phone',
        'status',
        'financial_status',
        'fulfillment_status',
        'currency',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total',
        'shipping_address_json',
        'billing_address_json',
        'discount_code',
        'payment_method',
        'note',
        'placed_at',
        'cancelled_at',
        'cancel_reason',
        'totals_json',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'financial_status' => FinancialStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
            'totals_json' => 'array',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<OrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /** @return HasMany<Fulfillment, $this> */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }
}
