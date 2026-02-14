<?php

namespace App\Http\Resources\Admin;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Discount
 */
class DiscountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'store_id' => (int) $this->store_id,
            'type' => $this->enumValue($this->type),
            'code' => $this->code,
            'value_type' => $this->enumValue($this->value_type),
            'value_amount' => (int) $this->value_amount,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'usage_limit' => $this->usage_limit === null ? null : (int) $this->usage_limit,
            'usage_count' => (int) $this->usage_count,
            'rules_json' => is_array($this->rules_json) ? $this->rules_json : [],
            'status' => $this->enumValue($this->status),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
