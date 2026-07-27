<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Services\NavigationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationMenuFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'handle',
        'title',
    ];

    /**
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        // Keep the cached navigation tree of this menu in sync.
        $flush = fn (NavigationMenu $menu) => app(NavigationService::class)->invalidate($menu->store_id, $menu->handle);

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Get the items of the menu in display order.
     *
     * @return HasMany<NavigationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'menu_id')->orderBy('position');
    }
}
