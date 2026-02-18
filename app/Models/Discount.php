<?php

namespace App\Models;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $store_id
 * @property string|null $code
 * @property string|null $title
 * @property DiscountType $type
 * @property DiscountValueType $value_type
 * @property int $value_amount
 * @property DiscountStatus $status
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property int|null $minimum_purchase_amount
 * @property array<string, mixed>|null $rules_json
 * @property array<string, mixed>|null $metadata
 */
class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'code',
        'title',
        'type',
        'value_type',
        'value_amount',
        'status',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_count',
        'minimum_purchase_amount',
        'rules_json',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'rules_json' => 'array',
            'metadata' => 'array',
        ];
    }
}
