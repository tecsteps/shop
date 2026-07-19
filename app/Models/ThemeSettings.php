<?php

namespace App\Models;

use App\Services\ThemeSettingsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSettings extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeSettingsFactory> */
    use HasFactory;

    /**
     * The primary key is the owning theme's id (one-to-one).
     *
     * @var string
     */
    protected $primaryKey = 'theme_id';

    /**
     * The primary key is not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table only has an updated_at column.
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
        'theme_id',
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
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        $flush = fn (ThemeSettings $settings) => app(ThemeSettingsService::class)
            ->invalidate($settings->theme?->store_id ?? Theme::withoutGlobalScopes()->whereKey($settings->theme_id)->value('store_id'));

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Get the theme these settings belong to.
     *
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
