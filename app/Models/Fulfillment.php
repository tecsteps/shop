<?php

namespace App\Models;

use App\Enums\FulfillmentShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fulfillment extends Model
{
    /** @use HasFactory<\Database\Factories\FulfillmentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FulfillmentShipmentStatus::class,
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<FulfillmentLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}
