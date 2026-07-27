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
     * The table has no timestamp columns.
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
     * Get the theme that owns the file.
     *
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
