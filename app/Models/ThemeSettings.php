<?php

namespace App\Models;

use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSettings extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    public $incrementing = false;

    protected $primaryKey = 'theme_id';

    protected $fillable = ['theme_id', 'settings_json'];

    protected function settingsJson(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                $settings = is_array($value) ? $value : json_decode((string) $value, true);

                return SafeUrl::sanitizeThemeSettings(is_array($settings) ? $settings : []);
            },
            set: fn (mixed $value): string => json_encode(
                SafeUrl::sanitizeThemeSettings(is_array($value) ? $value : []),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
