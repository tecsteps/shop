<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'code', 'title', 'type', 'value', 'minimum_subtotal_amount', 'usage_limit', 'usage_count', 'starts_at', 'ends_at', 'is_active', 'rules_json'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'rules_json' => 'array'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
