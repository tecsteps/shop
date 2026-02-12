<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToStore, HasFactory;

    const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'event_type',
        'properties_json',
        'session_id',
        'customer_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'properties_json' => 'array',
        ];
    }
}
