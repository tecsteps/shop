<?php

namespace App\Models;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'code',
        'type',
        'value_type',
        'value_amount',
        'status',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_count',
        'rules_json',
        'minimum_purchase_amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value_type' => DiscountValueType::class,
            'status' => DiscountStatus::class,
            'value_amount' => 'integer',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'minimum_purchase_amount' => 'integer',
            'rules_json' => 'array',
        ];
    }
}
