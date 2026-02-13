<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsEventFactory> */
    use BelongsToStore, HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'session_id',
        'event_type',
        'properties_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties_json' => 'array',
        ];
    }
}
