<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderExport extends Model
{
    /** @use HasFactory<\Database\Factories\OrderExportFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id', 'requested_by_user_id', 'format', 'filters_json', 'status', 'row_count',
        'storage_key', 'error_message', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'row_count' => 'integer',
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
