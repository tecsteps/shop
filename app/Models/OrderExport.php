<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderExport extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id', 'user_id', 'format', 'filters_json', 'status', 'row_count', 'storage_key', 'error_message', 'completed_at',
    ];

    protected $hidden = ['storage_key', 'error_message'];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'row_count' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
