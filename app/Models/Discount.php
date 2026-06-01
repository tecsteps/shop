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
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory;

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
            'value_amount' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'rules_json' => 'array',
            'status' => DiscountStatus::class,
        ];
    }

    /**
     * The minimum cart subtotal (cents) required, or null when unconstrained.
     */
    public function minimumPurchaseAmount(): ?int
    {
        $value = $this->rules_json['min_purchase_amount'] ?? $this->rules_json['minimum_purchase'] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * Product IDs the discount is restricted to, or null/empty for all.
     *
     * @return array<int, int>
     */
    public function applicableProductIds(): array
    {
        return array_map('intval', $this->rules_json['applicable_product_ids'] ?? []);
    }

    /**
     * Collection IDs the discount is restricted to, or null/empty for all.
     *
     * @return array<int, int>
     */
    public function applicableCollectionIds(): array
    {
        return array_map('intval', $this->rules_json['applicable_collection_ids'] ?? []);
    }
}
