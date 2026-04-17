<?php

namespace App\Models;

use App\Enums\FulfillmentShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property FulfillmentShipmentStatus $status
 * @property string|null $tracking_company
 * @property string|null $tracking_number
 * @property string|null $tracking_url
 * @property \Illuminate\Support\Carbon|null $shipped_at
 * @property-read Order $order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FulfillmentLine> $lines
 */
class Fulfillment extends Model
{
    /** @use HasFactory<\Database\Factories\FulfillmentFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'status',
        'tracking_company',
        'tracking_number',
        'tracking_url',
        'shipped_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FulfillmentShipmentStatus::class,
            'shipped_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<FulfillmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}
