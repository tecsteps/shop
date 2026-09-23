<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsExport extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsExportFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id', 'requested_by_user_id', 'from_date', 'to_date', 'channel', 'device', 'status',
        'storage_key', 'error_message', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date:Y-m-d',
            'to_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
