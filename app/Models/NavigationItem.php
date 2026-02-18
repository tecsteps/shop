<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_id
 * @property NavigationItemType $type
 * @property string $label
 * @property string|null $url
 * @property int|null $resource_id
 * @property int $position
 */
class NavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'type',
        'label',
        'url',
        'resource_id',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NavigationItemType::class,
            'position' => 'integer',
            'resource_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /**
     * @return HasMany<NavigationItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'menu_id', 'menu_id')
            ->where('position', '>', 0);
    }
}
