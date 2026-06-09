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
     * The table associated with the model.
     */
    protected $table = 'theme_settings';

    /**
     * The primary key is the owning theme's id (one-to-one).
     */
    protected $primaryKey = 'theme_id';

    public $incrementing = false;

    /**
     * The theme_settings table only has an updated_at column.
     */
    public const ?string CREATED_AT = null;

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
     * Invalidate the cached theme settings for the owning store on write.
     */
    protected static function booted(): void
    {
        $invalidate = function (ThemeSettings $settings): void {
            $storeId = $settings->theme()->withoutGlobalScopes()->value('store_id');

            if ($storeId !== null) {
                app(ThemeSettingsService::class)->forget((int) $storeId);
            }
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
