<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use App\Services\NavigationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationItemFactory> */
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
        'menu_id',
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
        ];
    }

    /**
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        // Keep the cached navigation tree of the parent menu in sync.
        $flush = fn (NavigationItem $item) => $item->flushMenuCache();

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Get the menu the item belongs to.
     *
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /**
     * Invalidate the cached tree of the parent menu.
     */
    protected function flushMenuCache(): void
    {
        $menu = $this->relationLoaded('menu') ? $this->menu : $this->menu()->first();

        if ($menu !== null) {
            app(NavigationService::class)->invalidate($menu->store_id, $menu->handle);
        }
    }
}
