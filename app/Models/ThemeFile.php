<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'byte_size' => 0,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('current_store', function (Builder $builder): void {
            if (! app()->bound('current_store')) {
                return;
            }

            $store = app('current_store');

            if (! $store instanceof Store) {
                return;
            }

            $builder->whereHas('theme', function (Builder $query) use ($store): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey());
            });
        });
    }

    /**
     * @return BelongsTo<Theme, $this>
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
        ];
    }
}
