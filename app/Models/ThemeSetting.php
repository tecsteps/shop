<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSetting extends Model
{
    protected $primaryKey = 'theme_id';

    public $incrementing = false;

    public $timestamps = false;

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
