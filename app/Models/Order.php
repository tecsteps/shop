<?php

namespace App\Models;

use App\Enums\OrderFinancialStatus;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'checkout_id',
        'order_number',
        'payment_method',
        'status',
        'financial_status',
        'fulfillment_status',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'total_amount',
        'email',
        'billing_address_json',
        'shipping_address_json',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'financial_status' => OrderFinancialStatus::class,
            'fulfillment_status' => OrderFulfillmentStatus::class,
            'billing_address_json' => 'array',
            'shipping_address_json' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }
}
