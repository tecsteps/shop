<?php

namespace App\Models;

use App\Enums\ExportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataExport extends Model
{
    /** @use HasFactory<\Database\Factories\DataExportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'type',
        'format',
        'status',
        'filters_json',
        'row_count',
        'storage_key',
        'error_message',
        'download_expires_at',
        'completed_at',
        'failed_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'orders',
        'format' => 'csv',
        'status' => 'queued',
        'filters_json' => '{}',
        'row_count' => 0,
    ];

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExportStatus::class,
            'filters_json' => 'array',
            'row_count' => 'integer',
            'download_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
