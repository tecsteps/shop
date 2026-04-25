<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'code', 'type', 'value_amount', 'value_bps', 'min_purchase_amount', 'usage_limit', 'used_count', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}

