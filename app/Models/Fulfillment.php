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

    /**
     * The table only has created_at, no updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'shipped_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FulfillmentShipmentStatus::class,
            'shipped_at' => 'datetime',
        ];
    }

    /**
     * Get the order the fulfillment belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the lines included in the fulfillment.
     *
     * @return HasMany<FulfillmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}
