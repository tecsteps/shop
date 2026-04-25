<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'order_id', 'amount', 'reason', 'restocked'];

    protected function casts(): array
    {
        return ['restocked' => 'boolean'];
    }
}

