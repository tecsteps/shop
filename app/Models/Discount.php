<?php

namespace App\Models;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value_type' => DiscountValueType::class,
            'status' => DiscountStatus::class,
            'rules_json' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'value_amount' => 'integer',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Scope to active discounts.
     *
     * @param  Builder<Discount>  $query
     */
    protected function scopeActive(Builder $query): void
    {
        $query->where('status', DiscountStatus::Active);
    }
}
