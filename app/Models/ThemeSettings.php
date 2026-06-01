<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSettings extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeSettingsFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'theme_settings';

    /**
     * The primary key is the theme foreign key, not auto-incrementing.
     *
     * @var string
     */
    protected $primaryKey = 'theme_id';

    /**
     * The primary key is the theme foreign key, not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The theme_settings table only tracks updated_at.
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
     * The theme these settings belong to.
     *
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
