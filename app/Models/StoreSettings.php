<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingsFactory> */
    use HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'store_id';

    protected $attributes = [
        'settings_json' => '{}',
    ];

    protected $fillable = [
        'store_id',
        'settings_json',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StoreSettings $settings): void {
            $settings->updated_at = now();
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
