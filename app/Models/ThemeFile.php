<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeFile extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeFileFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
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
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
