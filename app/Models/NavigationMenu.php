<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationMenuFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'handle',
    ];

    /** @return HasMany<NavigationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'menu_id');
    }
}
