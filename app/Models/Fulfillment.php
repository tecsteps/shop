<?php

namespace App\Models;

use App\Enums\FulfillmentShipmentStatus;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fulfillment extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id', 'status', 'tracking_company', 'tracking_number', 'tracking_url', 'shipped_at',
    ];

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

    protected function trackingUrl(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => SafeUrl::normalize($value),
            set: fn (mixed $value): ?string => SafeUrl::normalize($value),
        );
    }
}
