<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToStore;

    public const UPDATED_AT = null;

    protected $fillable = ['store_id', 'event', 'payload_json'];

    protected function casts(): array
    {
        return ['payload_json' => 'array'];
    }
}

