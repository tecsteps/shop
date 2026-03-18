<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSettings extends Model
{
    use HasFactory;

    protected $primaryKey = 'theme_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'theme_id',
        'settings_json',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
