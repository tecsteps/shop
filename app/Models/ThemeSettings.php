<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return ['settings_json' => 'array'];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
