<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'customer_id', 'order_number', 'email', 'status', 'financial_status', 'fulfillment_status', 'currency', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'shipping_address_json', 'timeline_json'];

    protected function casts(): array
    {
        return [
            'shipping_address_json' => 'array',
            'timeline_json' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }
}

