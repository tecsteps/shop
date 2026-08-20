<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeFile extends Model
{
    protected $fillable = ['theme_id', 'path', 'content'];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
