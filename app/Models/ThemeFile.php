<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeFile extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeFileFactory> */
    use HasFactory;

    /**
     * Theme files carry no timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'theme_id',
        'path',
        'storage_key',
        'sha256',
        'byte_size',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
        ];
    }

    /**
     * The theme this file belongs to.
     *
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
