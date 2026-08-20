<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'handle'];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('position');
    }
}
