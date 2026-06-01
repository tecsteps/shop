<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingsFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_settings';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'store_id';

    /**
     * The primary key is the store foreign key, not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The store_settings table only tracks updated_at.
     *
     * @var string|null
     */
    const CREATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'settings_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    /**
     * The store these settings belong to.
     *
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
