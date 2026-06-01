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

    /**
     * Navigation items carry no timestamps.
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
        'menu_id',
        'parent_id',
        'type',
        'label',
        'url',
        'resource_id',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NavigationItemType::class,
            'resource_id' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * The menu this item belongs to.
     *
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /**
     * The parent item, when this item is a nested submenu entry.
     *
     * @return BelongsTo<NavigationItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    /**
     * The child (submenu) items, ordered by position.
     *
     * @return HasMany<NavigationItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_id')->orderBy('position');
    }
}
