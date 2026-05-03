<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingsFactory> */
    use HasFactory;

    public const CREATED_AT = null;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'settings_json',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'settings_json' => '{}',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
