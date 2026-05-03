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

    /**
     * @var list<string>
     */
    protected $fillable = [
        'theme_id',
        'settings_json',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'settings_json' => '{}',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
