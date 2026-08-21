<?php

namespace App\Models;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'customer_id', 'checkout_id', 'order_number', 'currency', 'status', 'financial_status', 'fulfillment_status', 'payment_method', 'email', 'shipping_address_json', 'billing_address_json', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'placed_at', 'metadata'];

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'financial_status' => FinancialStatus::class, 'fulfillment_status' => FulfillmentStatus::class, 'shipping_address_json' => 'array', 'billing_address_json' => 'array', 'placed_at' => 'datetime', 'metadata' => 'array'];
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
