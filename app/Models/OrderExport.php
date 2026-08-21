<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderExport extends Model
{
    use BelongsToStore, HasFactory;

    /** @use HasFactory<\Database\Factories\OrderExportFactory> */
    protected $fillable = ['store_id', 'format', 'filters_json', 'status', 'row_count', 'storage_key', 'download_url', 'download_expires_at', 'completed_at', 'error_message'];

    protected function casts(): array
    {
        return ['filters_json' => 'array', 'download_expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
