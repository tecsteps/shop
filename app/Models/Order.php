<?php

namespace App\Models;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\RefundStatus;
use App\Models\Concerns\BelongsToStore;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'checkout_id',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'financial_status' => FinancialStatus::class,
            'fulfillment_status' => FulfillmentOrderStatus::class,
            'payment_method' => PaymentMethod::class,
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
     * Get the checkout the order was created from.
     *
     * @return BelongsTo<Checkout, $this>
     */
    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    /**
     * Get the customer that placed the order (null for unlinked guests).
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the lines in the order.
     *
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Get the payments recorded for the order.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the refunds recorded for the order.
     *
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get the fulfillments for the order.
     *
     * @return HasMany<Fulfillment, $this>
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }

    /**
     * Whether payment has been captured for the order.
     */
    public function isPaid(): bool
    {
        return $this->financial_status === FinancialStatus::Paid;
    }

    /**
     * Whether every line is a digital item (no shipping required). A line
     * whose variant was deleted counts as physical (fallback: shipping
     * required), so nothing is silently auto-fulfilled.
     */
    public function isDigital(): bool
    {
        $this->loadMissing('lines.variant');

        return $this->lines->isNotEmpty() && $this->lines->every(
            fn (OrderLine $line): bool => $line->variant !== null && ! $line->variant->requires_shipping
        );
    }

    /**
     * Amount that can still be refunded: total minus non-failed refunds.
     */
    public function refundableAmount(): int
    {
        $refunded = $this->refunds()
            ->whereIn('status', [RefundStatus::Pending->value, RefundStatus::Processed->value])
            ->sum('amount');

        return max(0, $this->total_amount - (int) $refunded);
    }

    /**
     * Formatted grand total for display, e.g. "65.45 EUR".
     */
    public function formattedTotal(): string
    {
        return Money::format($this->total_amount, $this->currency);
    }
}
