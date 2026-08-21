<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = ['shipping_zone_id', 'zone_id', 'name', 'type', 'price_amount', 'currency', 'config_json', 'is_active', 'estimated_days_min', 'estimated_days_max'];

    protected function casts(): array
    {
        return ['config_json' => 'array', 'is_active' => 'boolean'];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
