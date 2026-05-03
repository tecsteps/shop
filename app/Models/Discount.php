<?php

namespace App\Models;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use BelongsToStore, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'type',
        'code',
        'value_type',
        'value_amount',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_count',
        'rules_json',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => DiscountType::Code->value,
        'value_amount' => 0,
        'usage_count' => 0,
        'rules_json' => '{}',
        'status' => DiscountStatus::Active->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value_type' => DiscountValueType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'rules_json' => 'array',
            'status' => DiscountStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
