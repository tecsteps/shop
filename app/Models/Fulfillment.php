<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fulfillment extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'order_id', 'status', 'tracking_number', 'shipped_at', 'delivered_at'];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLine::class);
    }
}

