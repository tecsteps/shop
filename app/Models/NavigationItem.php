<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationItemFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'link',
        'position' => 0,
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

            $builder->whereHas('menu', function (Builder $query) use ($store): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey());
            });
        });
    }

    /**
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /**
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
}
