<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationItemFactory> */
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'type',
        'url',
        'resource_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => NavigationItemType::class,
        ];
    }

    /** @return BelongsTo<NavigationMenu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }
}
