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
     * The fulfillments table only tracks created_at.
     *
     * @var string|null
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
        'delivered_at',
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
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * The order this fulfillment belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The line allocations included in this fulfillment.
     *
     * @return HasMany<FulfillmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}
