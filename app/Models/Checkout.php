<?php

namespace App\Models;

use App\Enums\CheckoutStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'cart_id', 'customer_id', 'status', 'email', 'shipping_address_json', 'shipping_rate_id', 'payment_method', 'order_id', 'totals_json'];

    protected function casts(): array
    {
        return [
            'status' => CheckoutStatus::class,
            'shipping_address_json' => 'array',
            'totals_json' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

