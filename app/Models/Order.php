<?php

namespace App\Models;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $customer_id
 * @property string $order_number
 * @property PaymentMethod $payment_method
 * @property OrderStatus $status
 * @property FinancialStatus $financial_status
 * @property FulfillmentStatus $fulfillment_status
 * @property string $currency
 * @property int $subtotal_amount
 * @property int $discount_amount
 * @property int $shipping_amount
 * @property int $tax_amount
 * @property int $total_amount
 * @property string|null $email
 * @property array<string, mixed>|null $billing_address_json
 * @property array<string, mixed>|null $shipping_address_json
 * @property \Illuminate\Support\Carbon|null $placed_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderLine> $lines
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Refund> $refunds
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Fulfillment> $fulfillments
 * @property-read Customer|null $customer
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'financial_status' => FinancialStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'billing_address_json' => 'array',
            'shipping_address_json' => 'array',
            'placed_at' => 'datetime',
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * @return HasMany<Fulfillment, $this>
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }
}
