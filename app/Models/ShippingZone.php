<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'countries'];

    protected function casts(): array
    {
        return ['countries' => 'array'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}

