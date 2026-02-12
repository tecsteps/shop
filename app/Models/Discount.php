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

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'code',
        'title',
        'type',
        'value_type',
        'value_amount',
        'minimum_purchase_amount',
        'usage_limit',
        'times_used',
        'starts_at',
        'ends_at',
        'status',
        'applies_to_json',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value_type' => DiscountValueType::class,
            'status' => DiscountStatus::class,
            'applies_to_json' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'value_amount' => 'integer',
            'minimum_purchase_amount' => 'integer',
            'times_used' => 'integer',
        ];
    }
}
