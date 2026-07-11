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

    protected $fillable = ['store_id', 'type', 'code', 'value_type', 'value_amount', 'starts_at', 'ends_at', 'usage_limit', 'usage_count', 'rules_json', 'status'];

    protected $attributes = ['type' => 'code', 'value_amount' => 0, 'usage_count' => 0, 'rules_json' => '{}', 'status' => 'active'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected function casts(): array
    {
        return ['type' => DiscountType::class, 'value_type' => DiscountValueType::class, 'status' => DiscountStatus::class, 'rules_json' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
