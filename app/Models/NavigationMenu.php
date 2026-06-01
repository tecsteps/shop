<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\NavigationMenuFactory> */
    use HasFactory;

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
     * All items belonging to this menu (flat, both top-level and nested).
     *
     * @return HasMany<NavigationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'menu_id');
    }

    /**
     * Top-level items (no parent), ordered by position.
     *
     * @return HasMany<NavigationItem, $this>
     */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id')->orderBy('position');
    }
}
