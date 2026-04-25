<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'customer_id', 'currency', 'cart_version', 'status', 'discount_code'];

    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }
}

