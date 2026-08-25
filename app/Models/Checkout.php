<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    use BelongsToStore, HasFactory;

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

    protected function casts(): array
    {
        return [
            'shipping_address_json' => 'array',
            'billing_address_json' => 'array',
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
