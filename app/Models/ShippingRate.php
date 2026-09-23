<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = ['shipping_zone_id', 'name', 'type', 'price_amount', 'min_order_amount', 'max_order_amount', 'config_json', 'is_active'];

    protected function casts(): array
    {
        return ['config_json' => 'array', 'is_active' => 'boolean'];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
