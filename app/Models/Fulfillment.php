<?php

namespace App\Models;

use App\Enums\FulfillmentShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fulfillment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'status',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'shipped_at',
        'delivered_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FulfillmentShipmentStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fulfillmentLines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}
