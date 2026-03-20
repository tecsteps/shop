<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    use HasFactory;

    protected $table = 'store_settings';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected static function booted(): void
    {
        static::saving(function (StoreSettings $settings): void {
            $settings->updated_at = now();
        });
    }
}
