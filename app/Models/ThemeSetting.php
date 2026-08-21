<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSetting extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeSettingFactory> */
    use HasFactory;

    protected $primaryKey = 'theme_id';

    public $incrementing = false;

    public const CREATED_AT = null;

    protected $keyType = 'int';

    protected $attributes = ['settings_json' => '{}'];

    protected $fillable = ['theme_id', 'settings_json'];

    protected function casts(): array
    {
        return ['settings_json' => 'array'];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
