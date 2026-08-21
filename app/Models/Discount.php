<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'code', 'type', 'value_type', 'value_amount', 'status', 'usage_limit', 'usage_count', 'starts_at', 'ends_at', 'rules_json'];

    protected function casts(): array
    {
        return ['type' => DiscountType::class, 'value_type' => DiscountValueType::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'rules_json' => 'array'];
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active'
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture())
            && ($this->usage_limit === null || $this->usage_count < $this->usage_limit);
    }
}
