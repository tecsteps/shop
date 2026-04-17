<?php

namespace App\Models;

use App\Enums\FulfillmentShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fulfillment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'status',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'shipped_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FulfillmentShipmentStatus::class,
            'shipped_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}
