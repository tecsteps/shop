<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    /** @use HasFactory<\Database\Factories\ExportFactory> */
    use BelongsToStore, HasFactory;

    public const TypeOrders = 'orders';

    public const FormatCsv = 'csv';

    public const StatusQueued = 'queued';

    public const StatusProcessing = 'processing';

    public const StatusCompleted = 'completed';

    public const StatusFailed = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'type',
        'format',
        'status',
        'filters_json',
        'storage_key',
        'row_count',
        'download_expires_at',
        'completed_at',
        'failed_at',
        'failure_message',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => self::TypeOrders,
        'format' => self::FormatCsv,
        'status' => self::StatusQueued,
        'row_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'download_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
