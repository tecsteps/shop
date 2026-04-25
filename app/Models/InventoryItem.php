<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'variant_id', 'quantity_available', 'quantity_reserved', 'policy'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function availableForSale(): int
    {
        return max(0, $this->quantity_available - $this->quantity_reserved);
    }
}

