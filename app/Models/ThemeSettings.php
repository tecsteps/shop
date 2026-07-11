<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSettings extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeSettingsFactory> */
    use HasFactory;

    public const CREATED_AT = null;

    protected $primaryKey = 'theme_id';

    public $incrementing = false;

    protected $fillable = ['theme_id', 'settings_json'];

    protected $attributes = ['settings_json' => '{}'];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    protected function casts(): array
    {
        return ['settings_json' => 'array', 'updated_at' => 'datetime'];
    }
}
